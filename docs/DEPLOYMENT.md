# Deployment

The single runbook for releasing this app. Follow it top to bottom; the order matters in two
places and both are called out.

Target: `admin.empoweredhealth.asia` (EC2). Branch: `sprint1_dev`.

---

## 0. What is in this release

Three commits ahead of `origin/sprint1_dev`:

| Commit | Contents |
|---|---|
| `ef18346` | School parent roster, child seat caps, school payment history, Twilio/mail hardening |
| `cab87f1` | Direct-to-S3 video upload — large podcasts no longer pass through nginx/PHP, fixing HTTP 413 |
| `5b9be9c` | Seven pre-deploy blocker fixes (see §5) |
| _(this commit)_ | Pre-existing wiring defects found in review: Payment History DataTables column, unregistered reference seeders, four always-500 routes removed, jQuery downgrades, a global validation summary |

Every new enforcement rule is behind a **per-school flag that defaults to off**, so the deploy
itself changes no school's behaviour. Enabling is a separate, per-school step — §7.

> **Part B is still outstanding and none of it is code** — migrations, `QUEUE_CONNECTION=database`
> + cron, the S3 CORS rule with `ExposeHeaders: ETag` (without it the upload silently falls back
> and the 413 returns), the `assets/video/tmp/` lifecycle rule, and confirming SMTP before the
> first bulk parent mail.

That paragraph is the whole of §1 and §4. Nothing in it can be committed; all of it must be done
by hand around the deploy.

---

## 1. Pre-flight — before you deploy

These change nothing on their own, so do them ahead of time.

### 1.1 S3 bucket CORS — **the upload fix does nothing without this**

`ExposeHeaders: ETag` is not optional. Multipart completion needs the per-part ETag; without it
the browser uploader fails, silently falls back to posting the file through PHP, and large videos
fail with **413 again** — exactly the bug `cab87f1` exists to fix.

On the bucket in `AWS_BUCKET` → Permissions → CORS:

```json
[
  {
    "AllowedOrigins": ["https://admin.empoweredhealth.asia"],
    "AllowedMethods": ["PUT", "POST", "GET", "HEAD"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3000
  }
]
```

### 1.2 S3 IAM

The app's S3 identity (access key, or the EC2 instance role) needs, on top of the existing
`s3:PutObject` / `s3:GetObject` / `s3:DeleteObject`:

- `s3:AbortMultipartUpload`
- `s3:ListBucketMultipartUploads`
- `s3:ListMultipartUploadParts`

### 1.3 S3 lifecycle on the tmp prefix

Abandoned uploads otherwise accumulate as billable multipart parts forever.

- Prefix `assets/video/tmp/` → expire current versions after **1 day**
- `AbortIncompleteMultipartUpload` after **1 day** (bucket-wide is fine)

### 1.4 Confirm SMTP before the first bulk parent mail

`school_onboarded` and `signup_school_user` have **never delivered** and start delivering with
this release. This is the first time the app mails real parents in bulk.

- Confirm the production mailer actually sends.
- Confirm `MAIL_FROM_ADDRESS` is a domain you control, or the batch lands in spam.
- Admin **Settings** mail fields now overlay `config()` at send time, so a stale `.env` no longer
  silently wins. Check both.

### 1.5 Confirm which box you are on

The two historical descriptions of this host disagree, so verify rather than assume:

```bash
systemctl list-unit-files | grep -E 'php.*fpm|apache2|nginx'
ls -d /var/www/*
```

Older notes describe **Apache + mod_php at `/var/www/AdminPanel`**; the README describes
**nginx + php8.3-fpm at `/var/www/ec-healthcare`**. Whichever is real, use its path as `APP_PATH`
and its service in the reload step below.

---

## 2. Deploy the code

### 2.1 Manual — this is the path that works today

```bash
cd /var/www/ec-healthcare          # your APP_PATH from §1.5
sudo tar czf /var/www/backups/pre-deploy-$(date +%Y%m%d-%H%M%S).tar.gz .
git fetch origin
git reset --hard origin/sprint1_dev
composer install --no-dev --optimize-autoloader
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl reload php8.3-fpm   # or apache2
```

Take the snapshot first — it is the escape hatch in §8. Migrations are **not** in this block on
purpose; they are §3.

### 2.2 GitHub Actions — not available in this repo

Earlier notes describe `ci.yml`, `deploy-ec2.yml` and `rollback-ec2.yml` on a self-hosted runner.
**There is no `.github/` directory in this repository**, so none of that runs today. Treat the
runner path as unbuilt: either use §2.1, or add the workflows first.

If you do build it, the pieces those notes assumed:

| Environment secret | Example | Required |
|---|---|---|
| `APP_PATH` | `/var/www/ec-healthcare` | yes |
| `BACKUP_DIR` | `/var/www/backups/ec-healthcare` | no |
| `BACKUP_KEEP` | `5` | no |
| `PHP_BIN` | `/usr/bin/php8.3` | no |
| `PHP_FPM_SERVICE` | `php8.3-fpm` | recommended |

Actions variable `HEALTHCHECK_URL` (e.g. `https://admin.empoweredhealth.asia/login`) enables the
smoke test and auto-rollback; leave unset to skip both.

The runner user must be able to write `APP_PATH` while the web server user can still read it —
**the most common cause of a green deploy that serves a 500**:

```bash
APP_PATH=/var/www/ec-healthcare
RUNNER_USER=ubuntu

sudo usermod -aG www-data "$RUNNER_USER"
sudo chown -R "$RUNNER_USER":www-data "$APP_PATH"
sudo find "$APP_PATH" -type d -exec chmod 2775 {} +   # setgid: new files stay www-data
sudo find "$APP_PATH" -type f -exec chmod 0664 {} +
sudo chmod -R 2775 "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"

sudo tee /etc/sudoers.d/gh-runner-deploy >/dev/null <<'EOF'
ubuntu ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.3-fpm, /usr/bin/systemctl reload apache2, /usr/bin/systemctl list-unit-files
EOF
sudo chmod 0440 /etc/sudoers.d/gh-runner-deploy
sudo visudo -c
```

`.env` is never rsynced and is not in the repo — it must already exist on the box.

---

## 3. Migrations — after the code, before you announce it

```bash
php artisan migrate
php artisan school:backfill-contracts
```

Four migrations ship here: `2026_09_16_090000`, `090100`, `090200`, `2026_09_17_100000`.
Without them Payment History joins a `school_subscriptions` table that does not exist,
`SchoolController::store()` writes a `child_seat_limit` column that does not exist, and the mobile
`getProfile` `seats` block fails.

**Do not run `SchoolEmailTemplateSeeder` separately.** The alignment migration seeds all seven
templates itself, and re-running the seeder overwrites them, discarding any admin edits.

`school:backfill-contracts` is idempotent; the migration already backfills, this just proves it.

### 3.1 Reference data

```bash
php artisan db:seed --force
```

`CountrySeeder`, `NotificationTemplateSeeder` and `AdminMenuSeeder` were never registered, so on any
environment where they have not been run by hand:

- an empty `countries` leaves every country `<select>` with **no options**, so Add User and Sub Admin
  add/edit fail validation on `code` and bounce;
- an empty `notification_templates` makes `getNotificationContent()` return empty strings, so **every
  push and in-app notification ships with a blank title and body**.

Both truncating seeders now return early when their table already has rows, so this is safe to run
against a populated database. Confirm afterwards:

```bash
php artisan tinker --execute="echo App\Models\Country::count().' / '.DB::table('notification_templates')->count();"
```

Expect a non-zero pair (246 / 7 on a clean seed).

---

## 4. Queue — required, or no parent ever gets a password

The parent import **dispatches** `SendStudentSignupMail` instead of mailing inline.

- On `sync`, a dispatched job still runs inline: a 200-row import becomes 200 blocking SMTP calls
  in one request and times out.
- On `database` with nothing draining it, the jobs sit in the `jobs` table unsent, forever.

```env
QUEUE_CONNECTION=database
```

Then confirm something drains it:

```bash
crontab -l | grep schedule:run
```

Expect `* * * * * cd /var/www/ec-healthcare && php artisan schedule:run >> /dev/null 2>&1`.

`app/Console/Kernel.php` schedules `queue:work --stop-when-empty --max-time=55 --tries=3` every
minute with `withoutOverlapping(2)` — the explicit 2-minute expiry matters, because the default is
24 hours and one killed run would otherwise stop all queued mail for a day.

**If cron is absent**, either add that line or run a supervisor-managed `php artisan queue:work`
and delete the scheduled entry. Do not go live on the import path until one of the two is real.

The `jobs` table already exists (`2025_05_06_150335_create_jobs_table`).

---

## 5. What `8eef994` changed, and why each matters here

| Fix | Consequence if it were missing |
|---|---|
| Notification job loops unique recipients, not `DeviceToken` rows | A three-device parent got three in-app rows and three pushes to one handset; a parent with no token row got nothing at all |
| `seatThreshold()` wired into `addChild` | The 90% / 100% seat warning email existed but had no caller — schools learned they were full from a complaint |
| `uploadFileMatchesExisting()` compares an ETag via one `HeadObject` | It streamed the whole existing S3 object back through PHP to hash it; a large re-upload outran `max_execution_time` |
| `SchoolController::store()` deletes the school on every abort | A wrong spreadsheet header left an orphan row, so the admin could never re-create that school — name and code were "already taken" |
| `withoutOverlapping(2)` | One killed `queue:work` silenced all mail for 24 hours |
| Payment History filters on price, not `users.school_id` | Genuine App Store purchases by parents who later joined a school vanished from the revenue report |
| Entitlement check scoped to a live subscription | A parent with a lapsed personal plan got no entitlement when their school imported them |

---

## 6. Smoke tests

Run these in order. Each maps to something above.

```bash
php artisan migrate:status | grep 2026_09_16          # three Ran
php artisan tinker --execute="echo App\\Models\\EmailTemplate::count();"   # 9
php artisan schedule:list | grep queue
```

Then, in the admin UI and the app:

1. **Queue (§4)** — create a school with a 2-row parent spreadsheet.
   `php artisan tinker --execute="echo DB::table('jobs')->count();"` returns to 0 within a minute,
   and both parents receive credentials.
2. **Orphan school (§5)** — add a school with a deliberately wrong spreadsheet header. You get the
   error, *and* immediately re-submitting the same name and code succeeds.
3. **Notifications (§5)** — publish a podcast to a parent with two registered devices. Exactly one
   in-app notification appears.
4. **Upload (§1.1)** — upload a ~1.5 GB mp4 on Parent Video Webinars → create. In the browser
   network panel, no request to `admin.empoweredhealth.asia` is larger than a few hundred KB (the
   bytes go to the bucket host), there is **no 413**, and the saved row plays back.
5. **Upload fallback** — a ≤64 MB video still saves exactly as before.
6. **Payment History (§5)** — a parent with a real paid subscription who also belongs to a school
   appears in the list, and that row opens and downloads. A zero-price school grant does not appear.
7. **Reference data (§3.1)** — Admin → Users → Add creates a user. Submitting with no country
   selected now shows a visible error instead of bouncing silently.
8. **Notifications** — after publishing a podcast, the notification has a real title and body, and
   no literal `{video_link}` braces.
9. **Payment History** — loads with no DataTables warning dialog as both a modify and a view-only
   admin; View and Download work on an IAP row and a school row.
10. **Deep links still serve**:
   ```bash
   curl -sI https://admin.empoweredhealth.asia/.well-known/apple-app-site-association
   # 200, Content-Type: application/json, no Location: /login
   ```

Leave nginx `client_max_body_size` at **64M** — videos bypass it now, and raising it is not how
you allow bigger uploads. Keep the 300s `fastcgi_read_timeout` / `fastcgi_send_timeout`, because
the in-form path is still the fallback.

---

## 7. Per-school rollout (after the deploy is green)

Nothing below is required to deploy. Every flag defaults to off.

1. **Backfill the roster first.**
   ```bash
   php artisan school:backfill-roster --dry-run
   php artisan school:backfill-roster
   ```
2. **Set the caps** on the school's edit screen (`max_limit`, `child_seat_limit`,
   `per_parent_child_limit`). Blank means unlimited.
3. **Turn on `Only roster emails may join`.** The toggle refuses, and names the exact backfill
   command, if any of that school's parents are not yet on the roster — otherwise the flag locks
   out the very people it is meant to admit.

Each step is reversible by clearing the column.

> Known gap while rolling out: `max_limit` is enforced only on spreadsheet imports, not on parents
> who self-join with the school code. For a school that is capped commercially, turn on
> `enforce_parent_roster` — that is what actually gates self sign-up.

---

## 8. Rollback

**Code:**

```bash
cd /var/www/ec-healthcare
sudo tar xzf /var/www/backups/pre-deploy-<timestamp>.tar.gz
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl reload php8.3-fpm
```

Rollback restores **code, not the database**. If you ran §3, roll the schema back yourself first —
RDS snapshots are the DB rollback.

Be careful with the roster migration specifically: `2026_09_16_090100.down()` **drops
`school_parent_invites`**, losing the roster. Re-running `school:backfill-roster` rebuilds it from
existing parent accounts, but any revoked entries are gone.

---

## 9. Dependency locking (load-bearing, not housekeeping)

`composer.lock` is committed and `.gitignore` no longer excludes it.

Composer 2.9 refuses to **resolve** packages carrying security advisories, so with no lock file
every CI run and every deploy attempted a full re-resolve and failed outright:

```
Root composer.json requires laravel/framework ^11.9, found laravel/framework[v11.9.0, ..., v11.56.1]
but these were not loaded, because they are affected by security advisories
```

`composer install` against a lock file performs no resolution, so it installs the pinned versions
and that check never fires.

Composer 2.9 also **blocks installing** advisory-affected packages — a separate mechanism from
resolution and from auditing. Because the locked versions are affected, install commands pass
`--no-security-blocking`. Probe `composer install --help` for the flag before using it: it does
not exist on older Composer 2.x, where passing it is a hard error.

**Regenerating the lock.** `composer update` hits the same advisory wall. That is the tool working
correctly — resolve the advisories rather than switching the check off. `policy.advisories.block:
false` would silence it and quietly reintroduce the non-determinism this section prevents.

### Outstanding advisories

Committing the lock unblocked the pipeline; it did not make the dependencies safe. These are what
production runs today:

| Package | Locked | Status |
|---|---|---|
| `laravel/framework` | v11.45.1 | 7 advisories; every `^11.x` release is flagged, so this needs a Laravel 12 upgrade |
| `dompdf/dompdf` | v2.0.8 | 6 advisories; fixed in dompdf 3.x, via `barryvdh/laravel-dompdf` ^3.0 |
| `illuminate/mail` | v11.45.1 | PKSA-zwc5-qtrz-zm1n, same v11 constraint |

Plan the upgrade as its own piece of work — `laravel/cashier` and `yajra/laravel-datatables` both
constrain the framework version and will need bumping in the same change.

---

## 10. Known issues carried into this release

Not blockers, and none is made worse by deploying. Each deserves its own ticket.

- `sendNotificationSender()` builds a Firebase client **before** writing the in-app notification
  row, inside one try/catch — so a missing or rotated `storage/app/firebase/auth.json` silently
  costs users their in-app notifications, not just the push. It also builds one client per
  recipient, which is the real cost of a large audience.
- Imported spreadsheets are never deleted from `storage/app/uploads` — they hold parent PII.
- Import logic is duplicated across `SchoolController::store()`, `::update()` and
  `SchoolImportService`, and has already diverged in behaviour.
- The off-roster notice always reports one attempt; its `:pending` cache key is never cleared.
- `___sms_sender` failures are swallowed, so registration reports "OTP sent successfully"
  regardless of whether Twilio accepted it.
- Push payloads build `asset('assets/video/…')` URLs for files that live on S3.
- The direct-upload path skips the `mimes:` validation the in-form path applies.
- `child_seat_allocation` is stored on the roster but never enforced.
