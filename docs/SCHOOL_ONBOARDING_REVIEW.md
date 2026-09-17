# School Onboarding — Feature Review & Gap Analysis

## Context

The School Onboarding flow exists across the Admin Panel (`SchoolController`, `schoolManagement` views) and the API
(`RegisterService`, `HomeApiController::studentLogin`), but it was clearly built incrementally and never closed out.
This document is the **full feature review with the gaps listed**, plus a prioritized remediation plan.

Findings below are verified against the working tree on `sprint1_dev` (commit `3944822`), with file:line references.

**Companion document:** [`school-onboarding-actions.html`](school-onboarding-actions.html) — a summary deck of the same
findings as 22 prioritised action items across five phases, intended for client/stakeholder review. Also published at
<https://claude.ai/code/artifact/e29e19bb-1ed8-4714-b6f5-ef32ebead326>.

**How to read this document:** §0 covers the headline gaps that change the shape of the work. §A–F are the detailed
findings by category. §G is the remediation plan. Note that §G's phase numbering does not yet line up with the 22-item
numbering used in the companion deck — see the caveat at the end of §G.

---

## 0. Headline gaps

### 0.1 School onboarding details are collected but the school is never onboarded

`store()` collects and stores the school's contact `email` ([SchoolController.php:401](app/Http/Controllers/Admin/SchoolController.php:401)),
and then **nothing in the codebase ever uses it.** Every `___mail_sender` call in `SchoolController` targets a
*student* (`$student['email']`, `:525`, `:1031`), a *teacher* (`$row[1]`, `:1196`), or a deleted user (`:1268`). There is
**no confirmation/welcome mail to the school at all** — no credentials, no school code, no "your account is live".

The school is also never given an account: `schools.user_id` is the panel admin who created the row
(`'user_id' => auth()->user()->id`, `:398`). So after onboarding, the school has no login, no portal, no record of its
own code, and no email telling it any of this. **This is the single largest requirement gap** — onboarding currently
means "an admin typed a name into a table".

What's missing to close it: a school-contact user record (or a dedicated school role), a `school_onboarded` email
template carrying school name / code / plan / seat cap / login details, and a resend action from the school view.

### 0.2 The subscription offered to the school is unenforced and half-persisted

- `subscription_type` is validated as **`required` only** ([:376](app/Http/Controllers/Admin/SchoolController.php:376)) —
  no `in:monthly,quarterly,yearly`. Any string is accepted.
- The column it's written to **has no migration** (see B1).
- Both forms submit `quarterly`, but `subscriptions.subscription_type` is
  `enum('monthly','quaterly','yearly')` — **misspelled**
  ([create_subscriptions_table:20](database/migrations/2025_04_15_151018_create_subscriptions_table.php:20)). A quarterly
  school's subscription rows are rejected in strict mode, or stored as `''`.
- The date maths and the price/product-id ternaries disagree: an unrecognised type falls to the `default` arm
  (+1 month, `:502`) while the nested ternaries at `:507`/`:514` fall through to **yearly** ids and pricing. A malformed
  type therefore bills yearly for one month of access.
- Nothing displays the school's plan anywhere except the edit form's select.

### 0.3 There is no revenue model — at all

Confirmed by search: **no `plans`, `pricing`, `billing`, `invoices`, or `revenue` table exists** in
`database/migrations/`. What stands in for a revenue plan is three hardcoded price literals duplicated across three
files:

| Location | Values |
|---|---|
| [RegisterService.php:100-121](app/Services/RegisterService.php:100) | `com.empowered.{monthly,quarterly,yearly}`, `13.49 / 33.81 / 101.63`, `SGD` |
| [SchoolController.php:505-515](app/Http/Controllers/Admin/SchoolController.php:505) | identical literals |
| [SchoolController.php:1013-1026](app/Http/Controllers/Admin/SchoolController.php:1013) | identical literals |

Consequences:
- **Price changes require a code deploy** and must be made in three places consistently.
- `subscriptions` has **no `school_id` column** (verified: no migration adds one) — so a subscription granted *because
  of* a school is indistinguishable from a consumer purchase. **You cannot compute revenue per school.**
- `subscriptions` has **no `currency` column** in any migration either, yet all three sites write `'currency' => 'SGD'`
  — the same schema-drift class as B1.
- School-granted subscriptions are written with `status => 'Successful'` and price strings, with **no payment,
  no invoice, no billing record, and no distinction from a genuinely paid subscription**. A school seat and a paying
  consumer look identical in the data.
- There is no per-school contract value, billing cycle, renewal date, or seat-based pricing — `max_limit` caps seats but
  is not connected to price in any way.

To have a revenue story you need at minimum: a `plans` table (code, name, interval, price, currency, product ids)
referenced by `schools.plan_id`; `subscriptions.school_id` + `plan_id` + a `source` flag (`school` vs `purchase`); and
the three hardcoded blocks replaced by a lookup.

### 0.4 Imported parents and teachers do **not** receive their mail

The code calls `___mail_sender(..., 'signup_school_user', ...)` (`:525`, `:1031`) and
`___mail_sender(..., 'signup_teacher', ...)` (`:1196`). Both resolve the template from the `email_templates` table, or
fall back to a Blade view at `resources/views/emails/{template}.blade.php`
([helper.php:27-70](app/helper.php:27)).

Verified:
- Seeded templates are **only** `admin_otp` and `forgot_password` ([AdminOtpEmailTemplateSeeder.php:14,58](database/seeders/AdminOtpEmailTemplateSeeder.php:14)).
- `resources/views/emails/` contains **only** `admin_otp.blade.php`, `default.blade.php`, `forgot_password.blade.php`.
- So `signup_school_user`, `signup_teacher` **and** `delete_user_account` all hit the
  `Log::warning('Mail skipped: missing template'); return;` branch at [helper.php:66-69](app/helper.php:66).

**Result: on any environment without hand-inserted `email_templates` rows, every imported parent and teacher gets no
email and never learns their generated password — while the admin sees "N students added successfully" (`:529`).**
Because the password is random (`'Sch'.Str::studly(Str::random(4).'@1')`, `:482`) and never shown in the UI, those
accounts are permanently unreachable except via password reset.

Compounding it:
- Mails are sent **synchronously inside the import loop**, so a large file makes N blocking SMTP calls in one HTTP
  request. [`SendStudentSignupMail`](app/Jobs/SendStudentSignupMail.php) exists for exactly this and is **never
  dispatched**.
- There is no send-status tracking, no failure surface, and no "resend credentials" action anywhere.

### 0.5 The school's subscription is never managed, and there is no payment record behind it

**Nothing expires.** `ExpireSubscriptions` ([app/Console/Commands/ExpireSubscriptions.php](app/Console/Commands/ExpireSubscriptions.php))
exists and works, but its schedule entry is **commented out**:
`// $schedule->command('subscriptions:expire')->daily();` ([Kernel.php:28](app/Console/Kernel.php:28)) — the only
commented-out line among seven live ones. So every school-granted subscription stays `successful` **forever**, long past
its `end_date`. A one-month school grant is, in practice, permanent access. Nothing renews them either — there is no
renewal command, no notice before expiry, and no reconciliation against `schools.subscription_type`.

**There is no school-level subscription at all.** What `store()` and `RegisterService` create is **one
`subscriptions` row per imported user** — there is no single record representing "this school bought a yearly plan for
200 seats". Consequences:
- Changing a school's plan in Edit updates `schools.subscription_type` but **does not touch any existing
  `subscriptions` row** — already-imported users keep the old plan and old price indefinitely. The school's stated plan
  and its users' actual entitlements silently diverge.
- Users imported *after* a plan change get the new plan, so one school ends up with mixed plans and no record of why.
- Deactivating or deleting a school does **not** cancel or expire its users' subscriptions (`:906-908`, `:1233-1256`) —
  access survives offboarding.
- There is no suspend, no cancel, no upgrade/downgrade, no proration, no renewal date, and no seat reconciliation when
  `max_limit` is lowered.

**No payment is ever recorded.** School grants are written with `status => 'Successful'` and a hardcoded `price`, while
`receipt` and `transaction_id` ([2025_12_17_112311](database/migrations/2025_12_17_112311_add_receipt_and_transaction_id_to_subscriptions_table.php:14))
are left **null** — those columns exist for app-store purchases. So a school seat is recorded as a successful paid
subscription with **no payment method, no transaction, no receipt, no invoice and no amount actually collected**.
Combined with the missing `subscriptions.school_id` (0.3), there is no way to answer "what did this school pay, and for
what".

**Payment Management reports these as real revenue.** `PaymentManagementController` (menu 23,
[routes/web.php:325-328](routes/web.php:325)) lists **all** `subscriptions` rows joined to `users`, with no school
column, no school filter, and no distinction between a purchase and a school grant. Every free school seat appears in
payment history at its hardcoded price and is **downloadable as a PDF receipt for a payment that never happened**
(`historyDownload`, `:186`). Any revenue total taken from this screen is wrong by the entire school population.

Defects inside that controller, found while verifying:
- `historyDownload` / `historyView` identify the record by **`base64_decode($id)`** (`:83`, `:188`) — base64 is encoding,
  not authorisation. Ids are trivially enumerable, so any admin reaching the route can pull any user's receipt.
- `historyDownload:189-197` dereferences `$data->start_date` **before** the `if ($data)` guard at `:198` → fatal on an
  unknown id.
- `$expiresDate` is only assigned for `monthly` / `yearly` / **`quaterly`** (the misspelling, `:194`). School-granted
  rows are written as `quarterly`, so for exactly those rows `$expiresDate` is **undefined** when the PDF renders.
- Expiry on the PDF is **recomputed from `start_date`** rather than read from the stored `end_date`, so the receipt can
  contradict the database.
- `applyDateRangeFilter:112-115` does `explode(' - ')` with no bounds check and `Carbon::createFromFormat('d-m-Y', ...)`
  with no validation → 500 on malformed input.
- `historyIndex` has two near-identical DataTables branches (`:34-71`) differing only by the `is_modify` check, which
  changes nothing in the output — dead duplication.

**What closing this requires:** a school-level subscription record (school, plan, seats, term start/end, status) that
user entitlements derive from rather than duplicate; a payments/invoices table recording what was actually billed and
collected per school; a `source` flag so grants are excluded from revenue reporting; re-enabling `subscriptions:expire`;
and cancel/expire cascades on school deactivation and deletion.

### 0.6 School parents, children and teachers all have unlimited access — the subscription enforces nothing

Verified: **no server-side gate anywhere checks whether a subscription is active.**

- `ApiCheckStatus` ([app/Http/Middleware/ApiCheckStatus.php](app/Http/Middleware/ApiCheckStatus.php)) is the only
  middleware on the authenticated API group ([routes/api.php:54](routes/api.php:54)). It checks `user.status` and
  `school.status` — **and nothing about subscriptions**.
- The only live `Subscription::` reads in the API are in `getProfile`
  ([ChildController.php:600-612](app/Http/Controllers/Api/ChildController.php:600)), where the record is attached to the
  response **for display only**. Every other reference in that controller is commented out (`:332-335`, `:453-456`,
  `:590-594`, `:1034`).
- No content, video, quiz or knowledge-base endpoint consults a subscription before returning data.

So entitlement is, at best, enforced **client-side** from the `subscription` field in `getProfile` — which any modified
client or direct API call bypasses. Specifically for the school flow:

| Account | Subscription created? | Access if expired/absent |
|---|---|---|
| School parent (Excel import) | Yes — one row, hardcoded price | Unlimited; never expires (0.5) |
| School parent (self-signup by code) | Yes — [RegisterService:100](app/Services/RegisterService.php:100) | Unlimited; no seat cap (B4) |
| **Child** of a school parent | **No row of its own** — `getProfile` reads the *parent's* (`:607`) | Unlimited |
| **Teacher** (staff import) | **None at all** — staff import creates only a `User` ([SchoolController:1180](app/Http/Controllers/Admin/SchoolController.php:1180)) | Unlimited by construction |

Teachers are the clearest case: they are granted no entitlement record whatsoever, yet have full access. Combined with
0.5 (nothing expires) and B4 (no seat cap on self-signup), the practical position is that **the subscription system
records entitlements but grants nothing and withholds nothing.** Any revenue model built on top of it (0.3) is
unenforceable until a server-side gate exists.

Note this is not school-specific — consumer parents are equally ungated — but it matters most here, because school
users are precisely the population being issued paid-looking subscriptions for free.

### 0.7 No renewal or pre-expiry notice to anyone

There is **no 7-day (or any) expiry reminder**. Verified: no command, job, service or template exists for it. The
scheduler ([Kernel.php:18-31](app/Console/Kernel.php:18)) runs mood, notification, battery and audit tasks only — and
the one subscription-related entry, `subscriptions:expire`, is commented out (0.5). The single `'subscription'` mail
template referenced in code is itself commented out
([ChildController.php:1058](app/Http/Controllers/Api/ChildController.php:1058)).

Missing across the board: pre-expiry warning to the school (T-30/T-7/T-1), expiry notice, renewal confirmation,
seat-limit-reached alert to the school, and any equivalent to the end user. Because nothing expires today, none of these
would fire even if written — 0.5 must be fixed first.

### 0.8 Email inventory for this flow

**How mail works here:** `___mail_sender($to, $variable_name, $data, $lang)`
([helper.php:27](app/helper.php:27)) resolves the template from the `email_templates` table, falling back to
`resources/views/emails/{variable_name}.blade.php`, and **silently returns** if neither exists (`:66-69`).

**Critical constraint:** `EmailTemplateController::create()` and `store()` are **empty stubs**
([EmailTemplateController.php:69-79](app/Http/Controllers/Admin/EmailTemplateController.php:69)) — `index` only lists
rows that already exist, and `edit` is keyed by an existing `variable_name`. **An admin cannot add a missing template
from the panel.** Missing templates can only be introduced by a seeder or migration. This is why the gaps in 0.4 cannot
be worked around operationally.

**A. Referenced in code today (16) — status:**

| Template | Used for | Seeded? | Blade? | Sends? |
|---|---|---|---|---|
| `admin_otp` | Admin login OTP | Yes | Yes | ✅ |
| `forgot_password` | Password reset | Yes | Yes | ✅ |
| `signup_school_user` | **Imported parent credentials** | No | No | ❌ silent |
| `signup_teacher` | **Imported teacher credentials** | No | No | ❌ silent |
| `delete_user_account` | School user removed | No | No | ❌ silent |
| `signup_user`, `signup_subadmin`, `delete_account`, `delete_sub_admin_account`, `verification_email`, `otp`, `contact_reply`, `contact_support_user`, `child_support`, `child_support_to_parent` | various | No | No | ❌ silent |
| `subscription` | (call site commented out) | No | No | — |

Only **2 of 16** referenced templates actually deliver on a clean install.

**B. Emails this flow needs but has no call site for at all:**

*School-facing*
1. `school_onboarded` — welcome + school code + plan + seat cap + portal login (closes 0.1)
2. `school_credentials_resend` — re-issue the above on demand
3. `school_import_summary` — rows imported / skipped / failed, with reasons (closes the silent-skip gap, C4)
4. `school_seat_limit_reached` — `max_limit` hit during import or self-signup
5. `school_subscription_activated` — plan, term, seats, amount
6. `school_subscription_expiring` — T-30 / **T-7** / T-1 (closes 0.7)
7. `school_subscription_expired`
8. `school_subscription_renewed`
9. `school_plan_changed` — plan/seat change confirmation
10. `school_payment_receipt` — against a real recorded payment (closes 0.5)
11. `school_deactivated` / `school_reactivated`

*User-facing (school context)*
12. `school_user_welcome` — replaces the plaintext-password mail with a **set-password link** (see below)
13. `school_user_access_expiring` / `school_user_access_revoked` — when the school's term ends or the school is deactivated
14. `school_teacher_welcome` — teacher equivalent of 12

**C. Security note on the existing two.** `signup_school_user` and `signup_teacher` are designed to mail a
**plaintext generated password** ([SchoolController:482](app/Http/Controllers/Admin/SchoolController.php:482), `:1174`).
When implementing these, replace that with a single-use, expiring set-password link — it removes the plaintext-password
exposure and the weak generator in one change.

---

## A. Intended flow vs. what actually exists

| Step | Intended | Reality |
|---|---|---|
| 1. Admin creates a school | Name, code, contact email, seat cap, plan | Works, but writes to **columns that no migration creates** |
| 2. A "school admin" account is created | School logs in and manages itself | **Does not exist at all.** `schools.user_id` is the *panel admin who created the row*. There is no school-facing login, no school role, no invite |
| 3. Students/parents are onboarded | Bulk import or self-signup | Two divergent code paths with different rules (`store()` vs `update()`) |
| 4. Staff/teachers onboarded | Bulk import | Only available in **Edit**, not in Add — onboarding is a forced two-step |
| 5. Parents sign up in the app | School code links them | Works, but bypasses the seat cap and grants a free paid subscription |
| 6. Deactivation / offboarding | Suspend or remove a school | Half-implemented: deactivate cascades, **reactivate does not** |

---

## B. Blocking / correctness gaps

### B1. Schema drift — school creation cannot work on a clean database
`SchoolController::store` writes `max_limit`, `subscription_type`, `email`
([SchoolController.php:393-401](app/Http/Controllers/Admin/SchoolController.php:393)), `update` writes two of them
([:899-904](app/Http/Controllers/Admin/SchoolController.php:899)), `RegisterService` reads `subscription_type`
([RegisterService.php:84](app/Services/RegisterService.php:84)), and `edit.blade.php:82` renders `max_limit`.

`create_schools_table` creates only `id, name, school_code, user_id, status, timestamps`
([2025_02_20_110120](database/migrations/2025_02_20_110120_create_schools_table.php:14)). **No migration anywhere adds
those three columns** — production has been hand-patched. A fresh `migrate:fresh` breaks the whole feature.

### B2. `quarterly` is not a valid enum value
Both forms submit `quarterly` ([add.blade.php:147](resources/views/admin/schoolManagement/add.blade.php:147),
[edit.blade.php:148](resources/views/admin/schoolManagement/edit.blade.php:148)), but the column is
`enum('monthly','quaterly','yearly')` — misspelled
([create_subscriptions_table:20](database/migrations/2025_04_15_151018_create_subscriptions_table.php:20)).
Every quarterly-school subscription insert either throws (strict mode) or silently stores `''`.

### B3. `/api/student-login` — null deref, enumeration, no gating
[HomeApiController.php:690-733](app/Http/Controllers/Api/HomeApiController.php:690):
- `$user->school_id` is read at `:701` **before** the `!$user` guard at `:706` → fatal on any unknown email, swallowed
  into a generic error with **HTTP 200**.
- School lookup precedes the password check → "School code mismatch" vs "Invalid credentials" leaks account existence.
- No `school.status === 'active'` check, no `user.status` check, no mobile/email verification check, no rate limiting.
- Token created with no expiry, unlike `individualLogin:772`.
- `$language` hardcoded `"english"` in four places.

This matters more because `/api/login` explicitly excludes school users via `->whereNull('school_id')`
([:400](app/Http/Controllers/Api/HomeApiController.php:400)) — so `student-login` is the **only** path for a school
parent, and it is the weakest one.

### B4. Self-signup ignores the seat cap and grants free entitlements
`RegisterService::register` ([:28-121](app/Services/RegisterService.php:28)) validates only that the code exists and the
school is active. It never checks `max_limit`, and it creates a `Subscription` with hardcoded product ids and SGD prices.
A leaked school code = unlimited free paid accounts. `school_code` is also **not in the register validator**
([HomeApiController.php:77-81](app/Http/Controllers/Api/HomeApiController.php:77)).

Related: `updateParentProfile` ([:1092-1101](app/Http/Controllers/Api/HomeApiController.php:1092)) lets any parent
switch schools freely with no cap check, and `:1090` unconditionally writes `name`, **nulling it** when omitted.

### B5. Destructive deletes with no permission check
`destroy` ([SchoolController.php:1233-1256](app/Http/Controllers/Admin/SchoolController.php:1233)) has **no
`checkpermission` call**, no transaction, soft-deletes users, then hard-deletes the school (`School` has no
`SoftDeletes`). The `users.school_id` FK is `ON DELETE CASCADE`, so the hard delete wipes the user rows outright,
defeating the soft delete. `subscriptions`, `teacher_profiles`, `child_moods`, `user_attempt_quizzes` are left orphaned;
so are dead school ids inside `video_contents.school_id` JSON arrays.

`destroySchoolUser` ([:1258](app/Http/Controllers/Admin/SchoolController.php:1258)) has no permission check and **no
school scoping** — any user id can be passed (IDOR), and it mails the user before confirming the delete.

`exportSchoolUsers` ([:607](app/Http/Controllers/Admin/SchoolController.php:607)) and
`SchoolUserDetailController::allChildrenProgress` ([:260](app/Http/Controllers/Admin/SchoolUserDetailController.php:260))
also have no permission check.

### B6. No transactions anywhere
School row + N users + N subscriptions + N synchronous emails run unwrapped in `store()`
([:402-527](app/Http/Controllers/Admin/SchoolController.php:402)) and again in `update()`. Three early-return paths
(`:417` bad header, `:443` duplicate, `:472` limit reached) leave an **orphan school with zero students**; only the
`empty($validData)` path at `:464` rolls back. `:403 School::latest()->first()` re-fetches "newest school" instead of
using the created model — a genuine race under concurrent admins.

### B7. Bulk-import emails are silently dropped
The `signup_school_user` template has **no seeder and no Blade view** anywhere in the repo. `___mail_sender`
([helper.php:66-69](app/helper.php:66)) logs and returns silently when the template is missing — so generated passwords
are never delivered, while the admin sees "N students added successfully" (`:529`). Passwords are also weak by
construction (`'Sch' . Str::studly(Str::random(4) . '@1')`, `:482`) and mailed in plaintext.

Meanwhile [`SendStudentSignupMail`](app/Jobs/SendStudentSignupMail.php) — a queued mailer built for exactly this — is
**never dispatched**. Mails go out synchronously inside the row loop, so a large import will time out.

---

## C. Functional / UX gaps

1. **Add vs Edit are two different feature sets.** `store()` has no staff import; `update()` has no email update
   (`email` is neither validated nor written, and the field is `readonly` in `edit.blade.php:69`) — school contact email
   can never be changed after creation.
2. **Both files in one submit → staff silently ignored.** The student branch `return`s at `:1042`, so `staff_excel`
   uploaded alongside `student_excel` is dropped without a message.
3. **`max_limit` is not enforced for staff at all** — unlimited teachers regardless of cap.
4. **Import errors are invisible.** Student rows failing phone/email/country-code validation are silently `continue`d
   (`:428-458`); rows over the cap are silently `array_slice`d away (`:477`); the success message reports only the
   inserted count. Staff import is worse — *inconsistent*: bad fields hard-abort the whole file (`:1151/:1155/:1171`)
   while duplicates silently skip, with the intended aborts left commented out at `:1161` and `:1167`. Username format
   is validated **after** the duplicate lookup, so an invalid-but-existing username is never flagged.
5. **Duplicate rules differ between Add and Edit.** `store()` aborts on an email+phone match **anywhere** in `users`
   (`:437`, with the `school_id` scope commented out at `:439`, so its "already exists in this school" message is now
   wrong); `update()` scopes the same check to the school (`:945`).
6. **`status` is not settable at create.** Hardcoded `'active'` at `:396`; the status select is commented out in
   `add.blade.php:59-73`.
7. **Deactivation is one-way.** Setting a school inactive cascades `inactive` to every user (`:906-908`); reactivating
   restores nothing.
8. **Imported users never get a `status`** — they rely on the column default, so the cascade above is the only thing
   that ever writes it.
9. **School code generation is `rand()`** (`:184`) with no collision loop, and the field is `readOnly` in the form
   (`add.blade.php:47`) — on a collision the admin cannot fix it and must reload.
10. **`subscription_type` has no `in:` rule** (`:376`); an arbitrary value is stored and the nested ternaries at
    `:507`/`:514` silently bill it at yearly rates.
11. **No school logo/branding upload** exists anywhere in the flow.
12. **Uploaded filenames are unsanitized** — `storeAs('uploads', $id.'_'.$file->getClientOriginalName())` at `:407`
    and `:1129`.
13. **Read-only admins have no detail screen.** `index` gates on the permission row existing, but `show`/`create`/`edit`
    all require `is_modify == 'yes'` (`:182, :540, :691`), so a view-only admin can list schools but cannot open one.
14. **Permission menu-id mismatch.** `SchoolUserDetailController` checks menu **2** (User Management), not 3
    ([:26](app/Http/Controllers/Admin/SchoolUserDetailController.php:26), `:168`) — an admin with School-Management-only
    rights gets a 403 following a link from the school view. Denial style also differs (`redirect('dashboard')` vs
    `abort(403)`).
15. **`PermissionUser::checkpermission` does not short-circuit super-admin** the way the Blade `hasPermission()` helper
    does ([helper.php:805-817](app/helper.php:805)) — sidebar visibility and controller access follow different rules.

---

## D. Performance & data-integrity gaps

- `getSchools` ([:103-177](app/Http/Controllers/Admin/SchoolController.php:103)) is a DataTables server-side endpoint
  that calls **`->get()` not `->paginate()`** (`:135`) — every school plus two `withCount` subqueries is hydrated on
  every keystroke.
- Orderable whitelist (`:125`) includes `no_of_parent`, **a column that does not exist**; `index.blade.php` marks
  `child_count` orderable against a computed column the backend will never sort by.
- `view.blade.php` renders **every** user of a school in one unpaginated table (`:102`); filtering is client-side jQuery.
- XSS: `editColumn('name')` interpolates `$row->name` into a class attribute and text unescaped (`:154`) and registers
  it in `rawColumns` (`:162`). The `not_regex` input rule blocks tags but not attribute-breaking quotes.
- `moodsOverview` (`:1338-1413`) runs 2+ queries **per school inside a loop**; `start_date`/`end_date` are unvalidated
  raw strings fed to `Carbon::parse` → 500 on garbage input.
- `allChildrenProgress` (`SchoolUserDetailController:260-500`) — no validation on `start_date`/`end_date`, and
  `age_range` is `explode('-')` with no bounds check (`:277`). The select that fed it is commented out
  (`school_children_progress.blade.php:22-30`), so it is a dead parameter.
- `users.school_id` has no index beyond the FK; `users.email` is not unique, which is why duplicate detection is done
  with per-row `->exists()` queries.
- **Name collision:** `users.school_id` is a bigint FK, `video_contents.school_id` is a **JSON array**
  ([2026_03_26_124132](database/migrations/2026_03_26_124132_add_school_id_to_video_contents_table.php:14)) with no
  index. This forces duplicated int|string|array coercion in four controllers and makes `ApiCheckStatus:60` silently
  no-op on arrays.
- `ApiCheckStatus:65` does an uncached `DB::table('schools')` lookup on **every authenticated API request**.
- `RegisterService` writes subscriptions with `status => 'Successful'` (capital S) while the enum
  ([2025_12_17_151729](database/migrations/2025_12_17_151729_update_status_enum_in_subscriptions_table.php:17)) and
  `ChildController:601` both use lowercase. MySQL's default `_ci` collation resolves this to `successful`, so it works
  today — but it is a latent break on any case-sensitive collation and should be normalised.
- `RegisterService:55` hardcodes `user_role_id => 3` while `login:399` resolves it dynamically via the `roles` table.
- `RegisterService:74` — an unverified account can be **overwritten** by anyone knowing the email/phone, including its
  `school_id` and password. Account-takeover vector.
- Email verification is mailed (`:125-134`) but **never enforced** for school users on any login path.

---

## E. Dead code (should be removed as part of the cleanup)

| File | Lines | What |
|---|---|---|
| `SchoolController.php` | 40-101, 191-361, 548-605, 701-873, 1049-1123, 1219-1232, 1279-1298 | ~600 lines of superseded `getSchools`/`store`/`update`/`destroy`/export/staff-import |
| `app/Imports/StudentsImport.php` | whole file | Imports `App\Models\Student` but returns `new User(...)` — would fatal with `App\Imports\User not found`. Never referenced |
| `app/Exports/ExportSchool.php` | whole file | Never referenced; ignores `school_id`; compares `status` to ints `1`/`0` against an enum column → `$status` undefined at `:56` |
| `app/Jobs/SendStudentSignupMail.php` | whole file | Never dispatched |
| `app/Models/Student.php` + `create_students_table` | whole | Table and model entirely unused — real students are `users` rows |
| `resources/views/admin/rolePermission/*` | all 3 | Routes commented out at `routes/web.php:257-264` |
| `children_progress_chart.blade.php` + `SchoolUserDetailController@childrenProgress` | whole | Route `school-user-children-progress` referenced by no view |
| `school_user_detail.blade.php` | 37-39, 435-452 | A `<form>` with no submit button and all-readonly inputs; `toggleInputs()` targets `#imageInput`/`#videoInput` which don't exist → TypeError on every page load, killing later inline JS |
| `school_children_progress.blade.php` | 22-30, 55, 68, 182-196 | Age filter, pie canvases, pie JS; `$videoChartData` is hardcoded `[]` at `SchoolUserDetailController:491` |

Also: sidebar "Export School" is gated on `hasPermission(32)` ([headerFooter.blade.php:449](resources/views/layout/headerFooter.blade.php:449)),
a menu id **not seeded** in `AdminMenuSeeder` or `AdminUserSeeder` — invisible to everyone but role 1.

---

## F. Test coverage

**Zero.** `tests/Feature` contains only `DeepLinkBounceTest`, `DeepLinkCanonicalUrlTest`, `ExampleTest`. No
`SchoolFactory`, no test creates a `School` or exercises `RegisterService` with a school code or `ApiCheckStatus`.
The only school mention is three null-pinned fields in `tests/Feature/Api/snapshots/v1_contract.json`.

---

## G. Recommended remediation plan

### Phase 1 — Unblock (must ship first)
1. Add migration for `schools.max_limit` (unsigned int), `schools.subscription_type`, `schools.email`,
   `subscriptions.currency`, plus an index on `users.school_id`. Backfill-safe (`Schema::hasColumn` guards, matching the
   style of `2026_09_11_090000_align_schema_and_admin_otp_template.php`).
2. Migration to fix the `subscriptions.subscription_type` enum typo `quaterly` → `quarterly`, with a data update for
   existing rows. Normalise `status` casing at the same time.
3. **Close 0.4** — seed `signup_school_user`, `signup_teacher` and `delete_user_account` email templates (follow
   `AdminOtpEmailTemplateSeeder.php`) and add matching Blade fallbacks under `resources/views/emails/`. Change
   `___mail_sender` so a missing template returns a failure the caller can surface, instead of a silent `return`, and
   make the import report how many credential mails actually went out.

### Phase 1b — Close the onboarding and revenue gaps
1b-i. **Close 0.1** — create a school-contact user on `store()` (dedicated school role, linked via a new
   `schools.contact_user_id`, distinct from the existing `user_id` audit column), and send a `school_onboarded`
   confirmation mail containing school name, code, plan, seat cap and login details. Add a "Resend onboarding email"
   action to the school view.
1b-ii. **Close 0.2** — add `in:monthly,quarterly,yearly` to `subscription_type` in both FormRequests; replace the nested
   price/product-id ternaries with a single `match` so date, price and product id can never diverge; surface the plan on
   the school index and view screens.
1b-iii. **Close 0.3** — introduce a `plans` table (code, name, interval, price, currency, store product ids),
   point `schools.plan_id` at it, add `subscriptions.school_id` + `plan_id` + a `source` enum (`school_grant` vs
   `purchase`), and delete all three hardcoded price blocks in favour of a lookup. This is what makes per-school revenue
   reportable — until `subscriptions.school_id` exists, no revenue number can be attributed to a school.
1b-iv. **Close 0.5 — subscription lifecycle.** Add a `school_subscriptions` table (school, plan, seats, term
   start/end, status, renewal date) as the *single* record of what the school bought; make per-user `subscriptions`
   rows derive from it rather than each carry their own plan/price. Then:
   - Re-enable `$schedule->command('subscriptions:expire')->daily()` ([Kernel.php:28](app/Console/Kernel.php:28)) and
     normalise the status casing it filters on.
   - On plan change in Edit, re-issue/realign existing user subscriptions instead of leaving them stale.
   - On school deactivate/delete, cancel or expire the school's subscriptions inside the same transaction.
   - Reconcile seats when `max_limit` is lowered.
1b-v. **Close 0.5 — payment record.** Add a `school_payments` (or `invoices`) table — school, plan, period, amount,
   currency, method, reference, paid_at, status — and record every school billing event against it. Then fix Payment
   Management: filter/label by `source`, exclude `school_grant` rows from revenue totals, add school as a column and a
   filter, and stop issuing PDF receipts for grants that had no payment. Also fix the defects listed above — replace
   base64 ids with signed routes or authorised lookups, move the null guard before the dereference, drive expiry from
   the stored `end_date`, handle the `quarterly` spelling, and validate the date-range filter.
1b-vi. **Close 0.6 — actually enforce entitlement.** Add a subscription/entitlement check to `ApiCheckStatus` (or a
   sibling middleware on content routes) that resolves the effective entitlement: for a child, the parent's; for a
   school user, the school's term. Decide and implement the teacher rule explicitly — today they have no entitlement
   record and full access. Until this exists, the plan and revenue work in 1b-ii/iii/iv grants and withholds nothing.
1b-vii. **Close 0.7/0.8 — the email set.** Make templates deliverable first: seed the 14 unseeded `variable_name`s
   (or at minimum the school ones) **and** implement `EmailTemplateController::create()`/`store()`, which are empty
   stubs today — without that, admins cannot add a missing template from the panel at all. Then add the school-facing
   and user-facing templates listed in 0.8-B, plus a scheduled `subscriptions:notify-expiring` command firing the
   T-30/T-7/T-1 notices. Replace the plaintext-password mails with single-use set-password links.

### Phase 2 — Security
4. Add `PermissionUser::checkpermission` to `destroy`, `destroySchoolUser`, `exportSchoolUsers`,
   `allChildrenProgress`; scope `destroySchoolUser` to `where('school_id', ...)`. Make `checkpermission` short-circuit
   `user_role_id == 1` to match `hasPermission()`. Align `SchoolUserDetailController` on menu id 3.
5. Rewrite `studentLogin`: guard `!$user` before dereferencing, check the password **before** the school lookup, return
   one generic failure message, enforce `school.status`, `user.status` and verification, set a token expiry, throttle.
6. Enforce `max_limit` in `RegisterService::register` and in `updateParentProfile`; add
   `school_code` => `nullable|exists:schools,school_code` to the register validator; make `updateParentProfile` validate
   its input and stop nulling `name`.
7. Fix `RegisterService:74` — do not let an unverified record be silently overwritten by a different actor.

### Phase 3 — Correctness & consolidation
8. Extract `StoreSchoolRequest` / `UpdateSchoolRequest` FormRequests (`app/Http/Requests/Admin/`), with
   `subscription_type` => `in:monthly,quarterly,yearly`, `max_limit` => `integer`, `status` => `in:active,inactive`,
   and email uniqueness covering role 5.
9. Extract a single `SchoolImportService` used by **both** `store()` and `update()` — one duplicate rule, one cap rule
   (applied to staff too), one error model that returns a **per-row report** to the admin. Sanitize upload filenames.
   Wrap the whole thing in `DB::transaction`, and dispatch the existing `SendStudentSignupMail` job instead of mailing
   in-loop.
10. Allow staff import in Add; allow both files in one submit; make the school contact email editable.
11. Add `SoftDeletes` to `School`, change the `users.school_id` FK to `nullOnDelete` (or handle detach explicitly),
    and clean up `subscriptions` / `video_contents.school_id` JSON references on delete, inside a transaction.
12. Implement reverse activation: reactivating a school restores its users' status (track prior status, or restore only
    users deactivated by the cascade).
13. Generate `school_code` with a uniqueness loop rather than `rand()`.

### Phase 4 — Performance & cleanup
14. `getSchools`: switch to a paginated DataTables query; drop `no_of_parent` from the orderable whitelist; escape
    `name` and remove it from `rawColumns`. Paginate `view.blade.php`.
15. Validate date/age filters on `moodsOverview` and `allChildrenProgress`; flatten the per-school and per-child N+1
    loops into grouped aggregate queries.
16. Delete everything in section E.

### Phase 5 — Tests
17. Add `SchoolFactory`, then feature tests for: school create/update/delete permissions, bulk import (valid, invalid
    rows, over-cap, duplicate), `RegisterService` with valid/invalid/inactive/over-cap school codes, `studentLogin`
    negative paths, and `ApiCheckStatus` on an inactive school.

### Known caveat — numbering

This section was assembled incrementally and its numbering is not yet clean: items run 1–3, then 1b-i…1b-vii, then
restart at 4 and continue to 17. The companion deck presents the same work as a single sequence of **22 items across
P0–P4**, which is the numbering to quote in tickets and standups. Reconciling this section to that scheme is a pending
tidy-up; no finding or recommendation differs between the two.

A few findings are also stated twice — §0.2 overlaps B2, §0.4 overlaps B7, and §A summarises §0.1/0.3/0.5. The
duplicates agree with each other; they have not been merged.

---

## Verification

- `php artisan migrate:fresh --seed` must complete and school create/edit must work end-to-end on the fresh schema
  (this is the check that proves B1 is closed).
- Create a school with each subscription type; confirm a `subscriptions` row is written with the correct enum value.
- Import an Excel with a mix of valid rows, malformed phones, duplicates, and rows past `max_limit`; confirm the admin
  sees a per-row report and that counts reconcile.
- Confirm the signup email actually arrives (check `storage/logs` for the "Mail skipped" line disappearing).
- Register via API with a valid code, an invalid code, an inactive school, and a school at capacity.
- `POST /api/student-login` with an unknown email, a wrong password, and a deactivated school — all should return one
  generic message and no 500.
- Delete a school and confirm no orphan `subscriptions` / `teacher_profiles` / JSON `school_id` references remain.
- `php artisan test`.
