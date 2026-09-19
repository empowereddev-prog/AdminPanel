# Deployment

The runbook for releasing this app. Follow it top to bottom.

Target: `admin.empoweredhealth.asia` (EC2). Branch: `sprint1_dev`.

`scripts/deploy/ec2-release.sh` is the deploy. Everything below either sets it up, runs it, or
checks it. Do not hand-roll the steps it already performs — it knows things this document used to
get wrong, notably that `config:cache` breaks this app.

---

## 1. One-time setup

Do these once per environment. A fresh box is not deployable until all four are done.

### 1.1 Confirm the box and set `APP_PATH`

Two paths appear in this repo's history. Verify, don't assume:

```bash
ls -d /var/www/*
systemctl list-unit-files | grep -E 'php.*fpm|apache2|nginx'
```

The shipped systemd units (`scripts/deploy/ec2-*.service`) assume **`/var/www/AdminPanel` with
apache2**. If your box differs, edit the units before installing them. Whatever is real becomes
`APP_PATH` everywhere below.

`.env` is never in the repo and never rsynced — it must already exist at `$APP_PATH/.env`.

Then install the shared settings file. `ec2-release.sh` and both systemd units read it, so this is
the only place the paths are written down:

```bash
sudo cp "$APP_PATH/scripts/deploy/ec2-deploy.env" /etc/ec2-deploy.env
sudo ${EDITOR:-nano} /etc/ec2-deploy.env      # APP_PATH, GIT_BRANCH, PHP_BIN, PHP_FPM_SERVICE
```

Set `PHP_FPM_SERVICE` explicitly. The fallback probe cannot reliably tell a missing unit from a
present one, and a deploy that reloads nothing looks exactly like a deploy that worked.

**Sudoers.** The deploy runs as `ubuntu` but reloads the web server as root, so it needs a
password-less rule or every deploy silently skips the reload:

```bash
sudo tee /etc/sudoers.d/ec2-deploy >/dev/null <<'EOF'
ubuntu ALL=(root) NOPASSWD: /usr/bin/systemctl reload apache2, /usr/bin/systemctl reload php8.3-fpm, /usr/bin/systemctl list-unit-files
EOF
sudo chmod 0440 /etc/sudoers.d/ec2-deploy
sudo visudo -c
```

### 1.2 Queue worker — without this, no parent ever gets a password

The parent import **dispatches** `SendStudentSignupMail` rather than mailing inline, because
sending N credential emails inside one HTTP request times out a large import. Dispatching is not
sending: with `QUEUE_CONNECTION=database` and nothing draining the queue, the jobs sit in the
`jobs` table forever while the admin panel reports the import as a success. That is not
hypothetical — two jobs sat unsent for three days because the `schedule:run` cron this document
used to ask for was never installed.

Credential mail cannot depend on a manual step somebody may skip, so the worker ships with the
repo:

```env
QUEUE_CONNECTION=database
```

```bash
sudo cp "$APP_PATH/scripts/deploy/ec2-queue-worker.service" /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now ec2-queue-worker
systemctl is-active ec2-queue-worker      # active
```

`Restart=always` covers crashes, OOM kills and reboots. `--max-jobs=1000 --max-time=3600` recycle
the process so a long-lived PHP worker cannot accumulate leaked memory.

Two constraints that are not arbitrary:

- **`--timeout=60` must stay below `retry_after`** (90, in `config/queue.php`). A job that outlives
  `retry_after` is handed to a second worker while the first is still running it, and parents get
  their password twice. The dispatch sites also chunk recipients — `SendStudentSignupMail::CHUNK`,
  20 per job — so no single job approaches either number.
- **`app/Console/Kernel.php` must not also schedule `queue:work`.** Running both is worse than
  either: two workers, same duplicate-send race. If the unit is ever retired, restore a drain in
  the Kernel — do not leave the queue with no consumer.

`ec2-release.sh` runs `php artisan queue:restart` on every deploy, so the worker picks up new code
instead of running the version it booted with against freshly swapped files.

### 1.3 S3 — CORS, IAM, lifecycle

**CORS.** `ExposeHeaders: ETag` is not optional. Multipart completion needs the per-part ETag;
without it the browser uploader fails, silently falls back to posting the file through PHP, and
large videos fail with **413** — the exact bug direct-to-S3 upload exists to fix.

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

**IAM.** On top of `s3:PutObject` / `s3:GetObject` / `s3:DeleteObject`, the app's identity needs
`s3:AbortMultipartUpload`, `s3:ListBucketMultipartUploads`, `s3:ListMultipartUploadParts`.

**Lifecycle.** Abandoned uploads otherwise accumulate as billable multipart parts forever. On
prefix `assets/video/tmp/`: expire current versions after 1 day, and
`AbortIncompleteMultipartUpload` after 1 day.

### 1.4 SMTP

The app mails real parents in bulk on the import path. Before the first one:

- Confirm the production mailer actually sends — `MAIL_MAILER=log` renders to
  `storage/logs/laravel.log` and delivers nothing.
- Confirm `MAIL_FROM_ADDRESS` is a domain you control, or the batch lands in spam.
- Admin **Settings** mail fields overlay `config()` at send time, so a stale `.env` no longer
  silently wins. Check both.

---

## 2. Deploy

```bash
export APP_PATH=/var/www/AdminPanel
export GIT_BRANCH=sprint1_dev
export PHP_BIN=/usr/bin/php
export PHP_FPM_SERVICE=apache2

"$APP_PATH/scripts/deploy/ec2-release.sh" deploy
```

That snapshots the current app to `$BACKUP_DIR` (the escape hatch for §5), fetches, resets to the
branch, runs `composer install --no-dev`, clears and rebuilds caches, restarts the queue worker,
and reloads the web server.

**Migrations are deliberately not in that command.** Run them yourself in §3, after you have seen
the deploy succeed. `RUN_MIGRATIONS=true` exists on the script but leaves you less room to stop.

> **Never run `php artisan config:cache` on this app.** It calls `env()` at runtime outside
> `config/` — `AppServiceProvider`, `helper.php`, `Api/ChildController`. Caching config stops
> `.env` being read at all, and every one of those silently becomes `null`. `laravel_optimize()`
> in the script clears config rather than caching it, on purpose.

**Automatic deploys.** `ec2-autosync.timer` polls the branch every two minutes and redeploys when
it moves. Check with `systemctl list-timers | grep autosync`. Disable it while doing anything
manual, or it will fight you.

---

## 3. Migrations and reference data

```bash
cd "$APP_PATH"
php artisan migrate --force
php artisan school:backfill-contracts
```

`migrate` is what makes Payment History, child seat caps and the mobile `getProfile` `seats` block
work at all — without it they query tables and columns that do not exist.
`school:backfill-contracts` is idempotent; the migration already backfills, this just proves it.

### Seeders — name them, never run them all

> **Do not run `php artisan db:seed --force` on production.** It includes `AdminUserSeeder`, which
> `updateOrCreate`s `admin@empowered.local` with the hardcoded password `Admin@1234`, `status`
> active and `user_role_id` 1 — a full-privilege account with a password that is in this
> repository. Every run re-creates it and resets that password, so removing the account is not
> enough; it comes back on the next deploy.
>
> Check whether it is already present, on every environment:
>
> ```bash
> php artisan tinker --execute="echo App\Models\User::where('email','admin@empowered.local')->exists() ? 'PRESENT' : 'absent';"
> ```

Run only what the environment actually needs:

```bash
php artisan db:seed --force --class=CountrySeeder
php artisan db:seed --force --class=AdminMenuSeeder
php artisan db:seed --force --class=NotificationTemplateSeeder
```

`CountrySeeder` and `AdminMenuSeeder` truncate, but both return early when their table already has
rows, so they are safe against a populated database. They matter because an empty `countries`
leaves every country `<select>` with no options — Add User and Sub Admin bounce on validation — and
an empty `notification_templates` makes every push and in-app notification ship with a blank title
and body.

**These seeders overwrite, they do not merge.** `NotificationTemplateSeeder` and the three email
template seeders (`AdminOtpEmailTemplateSeeder`, `SchoolEmailTemplateSeeder`,
`AccountEmailTemplateSeeder`) `updateOrCreate` with no guard, so running one **discards any edits
an admin made in the panel**. That is how you push a copy change; it is also how you lose one. Run
them deliberately, not as a routine deploy step:

```bash
php artisan db:seed --force --class=SchoolEmailTemplateSeeder
```

Confirm:

```bash
php artisan tinker --execute="echo App\Models\Country::count().' / '.DB::table('notification_templates')->count();"
```

Expect a non-zero pair (246 / 7 on a clean seed).

---

## 4. Verify

```bash
systemctl is-active ec2-queue-worker
php artisan migrate:status | tail -5
php artisan tinker --execute="echo 'templates='.App\Models\EmailTemplate::count().' jobs='.DB::table('jobs')->count().' failed='.DB::table('failed_jobs')->count();"
```

Expect the worker `active`, all migrations `Ran`, 10 templates, and `jobs=0 failed=0`.

`failed_jobs` is written by the framework and **read by nothing**. `SendStudentSignupMail::failed()`
and `SendAdminNotification::failed()` log an exhausted job so it leaves a trace in
`storage/logs/laravel.log`, but no one is alerted. Check that count during any mail incident.

Then, in the admin UI:

1. **Credential mail** — create a school with a 2-row parent spreadsheet. `jobs` returns to 0
   within seconds and both parents receive credentials. Restart the worker mid-import
   (`sudo systemctl restart ec2-queue-worker`) and confirm nothing is lost.
2. **Large upload** — upload a ~1.5 GB mp4 on Parent Video Webinars → create. In the browser
   network panel no request to `admin.empoweredhealth.asia` exceeds a few hundred KB (the bytes go
   to the bucket host), there is no 413, and the saved row plays back. A ≤64 MB video still saves
   via the in-form fallback.
3. **Orphan school** — add a school with a deliberately wrong spreadsheet header. You get the
   error, *and* immediately re-submitting the same name and code succeeds.
4. **Notifications** — publish a podcast to a parent with two registered devices. Exactly one
   in-app notification, with a real title and body and no literal `{video_link}` braces.
5. **Payment History** — loads with no DataTables warning as both a modify and a view-only admin.
   Schools appear with their contract price; parents belonging to a school do not appear at all.
   View and Download work on the rows that are listed.
6. **Deep links**:
   ```bash
   curl -sI https://admin.empoweredhealth.asia/.well-known/apple-app-site-association
   # 200, Content-Type: application/json, no Location: /login
   ```

Leave nginx `client_max_body_size` at **64M** — videos bypass it now, and raising it is not how you
allow bigger uploads. Keep the 300s `fastcgi_read_timeout` / `fastcgi_send_timeout`, because the
in-form path is still the fallback.

---

## 5. Rollback

**Stop the autosync timer first.** Rollback puts git back in step with the restored files, which
means autosync can see that origin is ahead — and it will redeploy the release you just backed out
of, within two minutes. The script warns if the timer is running, but do it up front:

```bash
sudo systemctl stop ec2-autosync.timer
"$APP_PATH/scripts/deploy/ec2-release.sh" list
"$APP_PATH/scripts/deploy/ec2-release.sh" rollback latest    # or an archive name
```

The script preserves `.env` across the restore, resets git to the sha recorded beside the archive,
rebuilds the autoloader, clears caches and reloads.

Revert the bad commit on the branch before starting the timer again — otherwise the next tick
undoes the rollback:

```bash
sudo systemctl start ec2-autosync.timer
```

**Rollback restores code, not the database.** If you ran §3, roll the schema back yourself first —
RDS snapshots are the database rollback. Two migrations to be careful with:

- `2026_09_16_090100.down()` **drops `school_parent_invites`**, losing the roster.
  `school:backfill-roster` rebuilds it from existing parent accounts, but revoked entries are gone.
- `2026_09_19_100000.down()` drops `subscriptions.source`, after which Payment History falls back
  to the price heuristic and legacy school-onboarded parents reappear on the revenue report.

---

## 6. Per-school rollout

Not required to deploy. Every flag defaults to off, so the deploy itself changes no school's
behaviour.

1. **Backfill the roster first** — `php artisan school:backfill-roster --dry-run`, then without it.
2. **Set the caps** on the school's edit screen (`max_limit`, `child_seat_limit`,
   `per_parent_child_limit`). Blank means unlimited.
3. **Turn on `Only roster emails may join`.** The toggle refuses if any of that school's parents
   are not yet on the roster — otherwise the flag locks out the very people it is meant to admit.
   The refusal message tells the admin to contact you; the backfill command is in the log.

Each step is reversible by clearing the column.

> Known gap: `max_limit` is enforced only on spreadsheet imports, not on parents who self-join with
> the school code. For a school capped commercially, `enforce_parent_roster` is what actually gates
> self sign-up.

---

## 7. Dependency locking

`composer.lock` is committed, and this is load-bearing rather than housekeeping.

Composer 2.9 refuses to **resolve** packages carrying security advisories, so with no lock file
every deploy attempted a full re-resolve and failed outright. `composer install` against a lock
file performs no resolution, so the check never fires.

Composer 2.9 also **blocks installing** advisory-affected packages — a separate mechanism. Because
the locked versions are affected, `ec2-release.sh` passes `--no-security-blocking`, probing
`composer install --help` for the flag first: it does not exist on older Composer 2.x, where
passing it is a hard error.

`composer update` hits the same advisory wall. That is the tool working correctly — resolve the
advisories rather than switching the check off.

**Outstanding advisories.** Committing the lock unblocked the pipeline; it did not make the
dependencies safe. This is what production runs:

| Package | Locked | Status |
|---|---|---|
| `laravel/framework` | v11.45.1 | 7 advisories; every `^11.x` release is flagged, so this needs a Laravel 12 upgrade |
| `dompdf/dompdf` | v2.0.8 | 6 advisories; fixed in dompdf 3.x, via `barryvdh/laravel-dompdf` ^3.0 |
| `illuminate/mail` | v11.45.1 | PKSA-zwc5-qtrz-zm1n, same v11 constraint |

Plan the upgrade as its own work — `laravel/cashier` and `yajra/laravel-datatables` both constrain
the framework version and need bumping in the same change.

---

## 8. Known issues carried into this release

Not blockers, and none is made worse by deploying. Each deserves its own ticket.

- **No alerting on queue failure.** `failed_jobs` is never read; an exhausted job logs and is
  otherwise invisible. The admin sees "Credential emails are on their way" either way.
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
- The admin login page is the site root and is indexable; `public/robots.txt` allows everything.
