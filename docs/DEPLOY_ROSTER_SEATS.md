# Deploying the School Roster & Child Seats release

Companion to [`DEPLOYMENT.md`](DEPLOYMENT.md), which covers the mechanics (self-hosted runner, `run_migrations`,
rollback). This covers only what is specific to **this** release.

**Shape of the release:** every new enforcement rule is behind a per-school flag that defaults to off, so the deploy
itself changes no school's behaviour. The rollout is then per school, in a fixed order, and each step is reversible by
clearing a column.

**But it is not a pure no-op.** Section 2 lists the things that change the moment the code is live, whether or not you
enable a flag. Read that before scheduling.

---

## 1. Pre-flight — do these before you deploy

### 1.1 Set the queue driver. Non-negotiable.

The parent import now **dispatches** `SendStudentSignupMail` instead of mailing inside the request loop.

- If production has `QUEUE_CONNECTION=sync`, a dispatched job still runs **inline**, so a 200-row import becomes 200
  blocking SMTP calls in one HTTP request and will time out.
- If it is `database` but nothing drains the queue, the jobs sit in the `jobs` table and **no parent ever receives
  their password**.

So both of these must be true:

```env
QUEUE_CONNECTION=database
```

and a worker must actually run. This repo schedules one:

```php
// app/Console/Kernel.php
$schedule->command('queue:work --stop-when-empty --max-time=55 --tries=3')->everyMinute()->withoutOverlapping();
```

That only fires if **cron runs the scheduler**. Confirm on the box:

```bash
crontab -l | grep schedule:run
```

Expect a line like `* * * * * cd /var/www/app && php artisan schedule:run >> /dev/null 2>&1`.

- **If cron is present** — nothing more to do; the scheduled drain handles it.
- **If cron is absent** — either add it, or run a supervisor-managed `php artisan queue:work` and delete the scheduled
  entry. Do not deploy the import changes until one of the two is in place.

The `jobs` table already exists (`2025_05_06_150335_create_jobs_table`).

### 1.2 Confirm mail actually sends

Two templates that have **never delivered** start delivering with this release (§2.1). Before that happens, confirm the
production mailer works and `MAIL_FROM_ADDRESS` is a domain you control — this is the first time the app will send mail
to real parents in bulk.

### 1.3 Take the snapshot

The normal deploy takes one. Note the filename from the log; §6 needs it.

---

## 2. What changes the moment the code is live

These are **not** flag-gated. Each is deliberate; each is listed so nobody is surprised.

### 2.1 Imported parents and teachers start receiving email

`signup_school_user` and `signup_teacher` have had call sites and no template for as long as the importer has existed,
so `___mail_sender` hit its silent "missing template" return and every imported parent and teacher received nothing.
This release seeds both templates, so **mail starts flowing on the next import.**

That is the fix. But it means:

- the first import after deploy sends real email to real people;
- if the queue is not draining (§1.1), it silently sends nothing instead.

### 2.2 The parent limit is now optional

`max_limit` was `required`; it is now `nullable`, and blank means unlimited. Existing schools keep their current value.
A blank limit previously computed a negative remainder and refused every import — that is fixed.

### 2.3 `store()` stops counting teachers against the parent limit

`SchoolController::store` counted all users with `school_id`, including teachers, so a staff import ate parent places.
`update()` has always scoped this correctly; `store()` now matches. Schools that imported staff **gain back** the
places teachers were consuming — the limit loosens, never tightens.

### 2.4 A parent can no longer hop between schools by code

`updateParentProfile` let any parent attach to any school by typing its code, repeatedly. It now refuses when the
account is already linked to a different school. This tightens behaviour and is intentional; there is no legitimate
use of the old behaviour.

### 2.5 `School::latest()` race fixed in `store()`

`store()` re-read "the newest school in the table" after creating one, and fed that into a cleanup delete. Under two
admins creating schools at once it could **delete the other admin's school**. It now uses the model it just created.

### 2.6 Admin UI changes on the school view

- The user list is **parents only** and renamed *Parent Accounts*; the role filter is gone. Teachers moved to their own
  *Teacher Roster* card with import and listing.
- *Max Limit* is relabelled *Parent Limit* — it has always meant parents.
- Parent and teacher imports are available from the school view, not only from Edit.
- The import is described as creating **parent** accounts, because it always did.

### 2.7 Two robustness fixes with no visible behaviour change

- `___sms_sender` now guards missing Twilio config and catches `\Throwable`. A blank credential used to raise a
  `TypeError` that escaped the `catch (\Exception)` and turned registration into a 500.
- Admin AJAX URLs are now host-relative. Absolute URLs from `APP_URL` broke the session cookie whenever the browser was
  on a different host, surfacing as "CSRF token mismatch".

---

## 3. Deploy

Follow `DEPLOYMENT.md`'s **Release with migrations** path — Actions → *Deploy to EC2 (dev)* → tick `run_migrations`.

Three migrations ship:

| Migration | Does |
|---|---|
| `2026_09_16_090000_align_schools_table_and_add_seat_controls` | Adds the seat/roster columns; **backfills `schools.max_limit`, `subscription_type`, `email`**, which the code has always written but no migration ever created; indexes `users.parent_id` and `users.school_id`; seeds the seven email templates |
| `2026_09_16_090100_create_school_parent_invites_table` | The roster table |
| `2026_09_16_090200_add_currency_to_subscriptions_table` | Adds `subscriptions.currency`, written by three code paths and created by no migration |

> **Check whether `2026_09_11_090000_align_schema_and_admin_otp_template` is still pending in production.** It was
> pending on the dev machine. If it has not run there either, it will run as part of this deploy — it is a large
> additive alignment migration and is not part of this work. Confirm with `php artisan migrate:status` before ticking
> `run_migrations`.

### After migrating

```bash
php artisan migrate:status | tail -5
php artisan schedule:list | grep queue
```

If the email templates did not land (only possible if `2026_09_16_090000` had already run in that environment):

```bash
php artisan db:seed --class=SchoolEmailTemplateSeeder --force
```

> Re-running that seeder **overwrites** those seven templates, discarding admin edits. Run it deliberately, never as a
> routine deploy step.

---

## 4. Post-deploy verification, before enabling anything

```bash
php artisan migrate:status | grep 2026_09_16      # three Ran
php artisan tinker --execute="echo \App\Models\EmailTemplate::count();"   # 9 (2 pre-existing + 7 new)
```

Then, in the panel:

- Open a school. Parent Accounts, Parent Roster and Teacher Roster all render; the four cards line up.
- Create a school **without** a parent limit — it saves, and the view shows *Parent Limit: n / Unlimited*.
- Confirm the school received its onboarding email (new in this release — the contact address was collected and never
  used before).
- Import a small parent spreadsheet. Confirm accounts are created **and** that the jobs drain:

```bash
php artisan tinker --execute="echo \DB::table('jobs')->count();"   # should return to 0 within a minute
grep "Mail skipped: missing template" storage/logs/laravel.log     # should find nothing new
```

- Register through the mobile API with a school code — unchanged, because no flag is on yet.

**Do not proceed until the jobs table drains.** If it does not, §1.1 is not satisfied and imported parents are getting
no email.

---

## 5. Rollout, per school

Enforcement is per school and strictly ordered. The panel refuses step 3 until step 1 is clean.

**Step 1 — backfill the roster.**

```bash
php artisan school:backfill-roster --dry-run
php artisan school:backfill-roster
```

It writes a `claimed` invite for every existing school parent, is idempotent, never resurrects a revoked entry, and
ends with a verification block. Both counts must be **0**; it exits non-zero otherwise, so a deploy script can stop on
it. Duplicate emails within one school are reported and must be resolved by hand first.

**This must be clean before any flag is set.** Enabling enforcement on a school whose parents are not on the roster
locks every one of them out. The admin toggle enforces this and names the command to run.

**Step 2 — set the child seat limit.** Edit the school, set *Child Places* to what they paid for. *Children Per Parent*
pre-fills to 3 on new schools; blank means unlimited. Existing children are grandfathered — lowering a limit below
current usage is allowed and only blocks new creations.

**Step 3 — turn on `Only roster emails may join`.** Refused with the exact backfill command if any parent is off-roster.

**Step 4 — optionally turn off `Allow sign-up with school code`** for schools that onboard purely by import. This is the
strongest setting: the code cannot be used to register at all.

Start with one pilot school. Watch for `School roster join rejected` in the logs, and for the school reporting an
off-roster notice (throttled to one per school per hour).

---

## 6. Rollback

`DEPLOYMENT.md`'s *Rollback EC2* restores **code, not the database**.

**Preferred: disable, don't roll back.** Every enforcement rule is a column. Clearing them restores prior behaviour
without touching schema:

```sql
UPDATE schools SET enforce_parent_roster = 'no', self_signup_enabled = 'yes',
                   child_seat_limit = NULL, per_parent_child_limit = NULL;
```

That is the fast, safe escape hatch, and it is almost always the right one.

**If you must roll the schema back**, be aware:

- `2026_09_16_090100.down()` **drops `school_parent_invites`** — the roster is lost. Re-running the backfill rebuilds
  `claimed` rows from existing parents, but **revocations and any `invited`-only entries do not come back.** Dump the
  table first.
- `2026_09_16_090000.down()` deliberately does **not** drop `max_limit`, `subscription_type` or `email`. Those predate
  the migration in production and dropping them would destroy live data the app depends on.
- The code reads the flags as `?? 'no'` / `?? 'yes'`, so a code rollback ahead of a schema rollback degrades to legacy
  behaviour rather than erroring.

---

## 7. Known gaps shipping with this release

Called out so they are decisions, not surprises.

- **The import logic exists in three places** — `store()`, `update()`, and the new `SchoolImportService` used by the
  school-view uploads. The service is the tested one. Consolidating the other two is a clean follow-up.
- **The two import paths differ on messy spreadsheets.** `store()`/`update()` abort the whole file on a duplicate and
  silently drop malformed rows; the service counts skipped and rejected rows, imports the rest, and reports the split.
  Worth aligning.
- **Passwords are still generated and emailed in plaintext**, using `'Sch' . Str::studly(Str::random(4) . '@1')`. A
  set-password link would remove both the plaintext exposure and the weak generator; that was explicitly deferred.
- **Email templates are English only.** `EmailTemplateController::update()` renders a Chinese field but saves only
  English, so an admin editing a template in Chinese loses their work with no error. Separate ticket.
- **`EmailTemplateController::create()`/`store()` remain empty stubs**, so new templates can only be introduced by a
  seeder.
- **Multi-process concurrency on `add-child` was never exercised.** The seat check takes `SELECT … FOR UPDATE` and a
  test asserts the lock is requested, but single-process PHPUnit cannot prove two requests contend. Worth one manual
  check on staging at a school with exactly one place left.
