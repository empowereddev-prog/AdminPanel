# School ↔ Parent ↔ Child — Relationship Integrity & Child Seat Management

**Scope:** two defects in the school onboarding flow —
1. a school code shared with a non-school parent grants that parent full app access;
2. the seat cap the school paid for (`max_limit`) does not constrain child account creation at all.

**Constraint:** no existing functionality may regress. Every behavioural change in this plan is placed behind a
per-school flag that defaults to *off*, so deploying the code changes nothing until a school is explicitly opted in.

Verified against the working tree on `sprint1_dev` (commit `3944822`).

**Companion deck** (same plan, for stakeholder review):
<https://claude.ai/code/artifact/e307e5fa-fda5-49e5-a258-a713878079ce>

Companion to
[`SCHOOL_ONBOARDING_REVIEW.md`](SCHOOL_ONBOARDING_REVIEW.md), which catalogues the wider gaps; this document covers only
these two and is self-contained.

---

## 1. Ground truth — what the relationship actually is today

This is the part most of the confusion comes from, so it is stated precisely before anything else.

### 1.1 There is no parent/child table in the flow — everything is `users`

| Actor | Row | Role | Key columns |
|---|---|---|---|
| School | `schools` | — | `school_code` (unique), `status`, `user_id` = *the panel admin who created the row*, plus hand-patched `max_limit`, `subscription_type`, `email` |
| Parent | `users` | `user_role_id = 3`, `user_type = 'parent'` | `school_id` FK → `schools.id` |
| Teacher | `users` | `user_role_id = 5`, `user_type = 'teacher'` | `school_id` FK ([SchoolController.php:1180](../app/Http/Controllers/Admin/SchoolController.php:1180)) |
| **Child** | `users` | `user_role_id = 4`, `user_type = 'child'` | **`parent_id` only — `school_id` is never set** ([ChildController.php:91-104](../app/Http/Controllers/Api/ChildController.php:91)) |

Two traps worth naming explicitly, because both will bite anyone implementing this:

- **The `children` table is a decoy.** `create_children_table` exists and `App\Models\Child` is still used by
  `DashboardController::index`, `UsersController`, and `ChildController::primaryChild`
  ([:679](../app/Http/Controllers/Api/ChildController.php:679)) — but `addChild` creates a **`User`**, not a `Child`.
  Real children are `users` rows. Any seat count written against the `children` table will read zero.
- **`video_contents.school_id` is a JSON array**, while `users.school_id` is a bigint FK. Same name, different type.
  This is why `ApiCheckStatus::schoolIsInactive` carries an `is_array()` guard
  ([:60](../app/Http/Middleware/ApiCheckStatus.php:60)).

### 1.2 The consequence: a child is invisible to every school-scoped rule

Because a child row carries no `school_id`, a child is not reached by:

- `ApiCheckStatus::schoolIsInactive` ([:58-68](../app/Http/Middleware/ApiCheckStatus.php:58)) — **a child of a school
  parent keeps working after the school is deactivated**, even though the parent is locked out;
- the `max_limit` count in the import (§3.1);
- `withCount` figures on the admin school list;
- school-scoped content filtering, which resolves the *parent's* `school_id`
  ([KnowledgeBaseController.php:1056-1060](../app/Http/Controllers/Api/KnowledgeBaseController.php:1056)) — this one
  works, and is the pattern to copy.

The only reliable way to attribute a child to a school today is through the parent:

```sql
SELECT COUNT(*)
FROM users c
JOIN users p ON p.id = c.parent_id
WHERE p.school_id = ?
  AND c.user_role_id = 4
  AND c.deleted_at IS NULL
```

This query needs no schema change and is the seat-count primitive used throughout §4.

### 1.3 The three ways a `users.school_id` gets set

| # | Path | Code | Who controls it |
|---|---|---|---|
| 1 | Admin Excel import | [SchoolController::store:479-497](../app/Http/Controllers/Admin/SchoolController.php:479), `update:~990` | Admin — school supplied the list. **Trusted.** |
| 2 | Self-signup with `school_code` | [RegisterService::register:28-56](../app/Services/RegisterService.php:28) | Anyone holding the code. **Untrusted.** |
| 3 | Profile update with `school_code` | [HomeApiController::updateParentProfile:1092-1101](../app/Http/Controllers/Api/HomeApiController.php:1092) | Any already-registered parent. **Untrusted.** |

Paths 2 and 3 are glitch #1 in its entirety.

---

## 2. Glitch #1 — code sharing grants school access

### 2.1 Root cause

`school_code` is being used as **proof of membership**, but it is a static, non-expiring, non-rotatable, shared secret
that is printed in every welcome email and visible in every parent's profile response
([ChildController::getProfile:627-632](../app/Http/Controllers/Api/ChildController.php:627) returns
`school_code` to the client). One parent forwarding one email is a permanent, unlimited breach.

`RegisterService::register` validates only that the code **exists** and the school is **active**
([:29-31](../app/Services/RegisterService.php:29)). There is no check that the registering person is someone the school
actually named. The school's parent list — the one authoritative fact in this whole flow — is consumed once by the Excel
importer to create `users` rows and then **thrown away**. It is never stored as a roster, so there is nothing left to
check a self-signup against.

Aggravating factors, all verified:

- `school_code` is **not in the register validator** ([HomeApiController.php:77-97](../app/Http/Controllers/Api/HomeApiController.php:77)) —
  it is only passed through in the `only([...])` list. No `exists:` rule, no format rule.
- A successful school signup is granted a **free subscription** priced at `13.49 / 33.81 / 101.63 SGD`
  ([RegisterService.php:100-121](../app/Services/RegisterService.php:100)). The breach is not just access, it is
  revenue — and those rows land in Payment Management as apparent revenue.
- `updateParentProfile` ([:1092-1101](../app/Http/Controllers/Api/HomeApiController.php:1092)) lets an **existing
  consumer parent** attach themselves to a school after the fact, with no cap and no re-verification. It also
  unconditionally writes `'name' => $request->name`, nulling the name when the field is omitted.
- `/api/login` deliberately excludes school users via `->whereNull('school_id')`
  ([:400](../app/Http/Controllers/Api/HomeApiController.php:400)), so `student-login` is the only door for a school
  parent — and it resolves the school from `$user->school_id` **before** the `!$user` guard
  ([:701-706](../app/Http/Controllers/Api/HomeApiController.php:701)), fatals on an unknown email, and leaks account
  existence by distinguishing "School code mismatch" from "Invalid credentials".

### 2.2 Design — make the roster the authority, demote the code to a hint

The fix is a **`school_parent_invites` roster**: the school's list of parent emails becomes a first-class, persisted
allowlist instead of a one-shot import input. Membership is then decided by *"is this email on this school's roster?"*,
and `school_code` degrades to a convenience for routing the signup to the right school — worthless on its own.

```
schools (1) ──< school_parent_invites (email allowlist, one row per named parent)
                        │ claimed_user_id
                        ▼
                    users (parent, role 3, school_id)
                        │ parent_id
                        ▼
                    users (child, role 4)   ← seats are counted here (§4)
```

**Table: `school_parent_invites`**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `school_id` | FK → schools, cascade | |
| `email` | string(191), indexed | normalised lowercase/trim |
| `name`, `country_code`, `phone_no` | nullable | from the Excel row, for pre-fill and matching |
| `status` | enum `invited,claimed,revoked` | default `invited` |
| `claimed_user_id` | nullable FK → users | set once, on claim |
| `child_seat_allocation` | unsigned small int, nullable | optional per-parent override (§4.4) |
| `invited_at`, `claimed_at` | timestamps nullable | |
| | | **unique(`school_id`, `email`)** |

Three properties do the actual work:

1. **Allowlist** — a self-signup whose email is absent from the school's roster is not a school signup.
2. **Single claim** — `claimed_user_id` is written once inside a transaction. Even if a real roster email leaks, the
   second person to try it is rejected, because the row is already bound.
3. **Revocable** — a school can revoke a parent without deleting the user, and revocation is a roster state change
   rather than a destructive action.

**Why the roster rather than just rotating the code:** rotation is a mitigation with a permanent re-sharing problem —
the new code leaks the same way the old one did. The roster changes the security property from *"knows a secret"* to
*"is on a list the school controls"*, which is the requirement as actually stated. Rotation is still worth adding
(§2.4) as defence in depth.

### 2.3 Enforcement points

All three are behind `schools.enforce_parent_roster` (default `false` → today's behaviour exactly).

**A. `RegisterService::register`** — after the school lookup at [:29](../app/Services/RegisterService.php:29), before
`User::create` at [:80](../app/Services/RegisterService.php:80):

```php
if ($school && $school->enforce_parent_roster) {
    $invite = $roster->findClaimable($school, $data['email']);   // status invited|claimed-by-this-email
    if (!$invite) {
        return ['status' => false, 'message' => /* generic, see below */, 'user' => null];
    }
}
```

The generic message matters: it must not distinguish "not on the roster" from "already claimed", or the endpoint
becomes a roster-enumeration oracle. Recommended copy — *"This school code cannot be used with this email address.
Please use the email address your school registered for you, or contact your school."* — which is actionable for a
legitimate parent and useless to an attacker.

The claim itself (`status = claimed`, `claimed_user_id = $user->id`) is written **in the same transaction** as
`User::create`, with the invite row selected `lockForUpdate()`. Note that `RegisterService::register` currently has
**no transaction at all**; one must be introduced around the user/subscription/claim writes.

**B. `HomeApiController::updateParentProfile`** ([:1092-1101](../app/Http/Controllers/Api/HomeApiController.php:1092)) —
identical roster check before `$updateData['school_id'] = $school->id`. Additionally, and independent of the roster
work: validate the request, and stop writing `name` when it is absent.

**C. The admin import becomes the roster writer.** In `SchoolController::store` and `update`, every accepted Excel row
writes a `school_parent_invites` row (`status = claimed`, `claimed_user_id` = the created user) alongside the existing
`User::create`. Rows skipped as duplicates are still written as `invited`, which incidentally gives the admin the
per-row visibility that is missing today. **The import's own behaviour is unchanged** — it already only creates users
the school named, so it is trusted by construction.

### 2.4 Supporting hardening (small, independent, each shippable alone)

| Change | Where | Why |
|---|---|---|
| `school_code` → `nullable|string|exists:schools,school_code` in the register validator | [HomeApiController.php:77](../app/Http/Controllers/Api/HomeApiController.php:77) | Currently unvalidated; rejects garbage before the service layer |
| Require verified email before `school_id` takes effect | `RegisterService` / verification callback | Registration already mails a verification link ([:125-134](../app/Services/RegisterService.php:125)) and it is never enforced. Roster + verified email means the claimer must control the school-registered inbox — this is what makes a leaked roster email unusable |
| `schools.self_signup_enabled` toggle | new column | Schools that onboard purely by import can switch path 2 off entirely — the strongest possible fix for them, and a one-click answer for the affected school today |
| Rotate `school_code` + `code_rotated_at` | admin school view | Invalidates an already-leaked code. Pair with a re-issue email |
| Fix `RegisterService:74` | [:72-76](../app/Services/RegisterService.php:72) | An **unverified** existing account is silently `update()`d with the new payload, including `school_id` and `password`. Anyone knowing an unverified user's email can take the account over *and* pull it into a school. This bypasses the roster check as written, so it must be fixed in the same change |
| Rewrite `studentLogin` | [:690-733](../app/Http/Controllers/Api/HomeApiController.php:690) | Null-deref before guard, account enumeration, no `status` checks, no token expiry, no throttle |

The `RegisterService:74` item is not optional. With the roster in place but that path unfixed, an attacker registers an
unverified account at the roster email first, then updates it — the claim check never runs on the update branch.

---

## 3. Glitch #2 — the seat cap does not count children

### 3.1 What `max_limit` does today

It is read in exactly two live places, both in the admin importer
([store:467-478](../app/Http/Controllers/Admin/SchoolController.php:467),
[update:975-984](../app/Http/Controllers/Admin/SchoolController.php:975)):

```php
$existingStudents = User::where('school_id', $school->id)->count();
$remainingLimit   = $request->max_limit - $existingStudents;
$validData        = array_slice($validData, 0, $remainingLimit);   // silent truncation
```

Four distinct problems in five lines:

1. **`User::where('school_id', ...)` counts parents *and teachers*** — teachers are created with `school_id` at
   [:1180](../app/Http/Controllers/Admin/SchoolController.php:1180). Importing staff consumes parent seats.
2. **It never counts children**, which have no `school_id` (§1.2). A school with a 200 cap and 200 imported parents can
   hold 2,000 children.
3. **`addChild` has no cap of any kind** — not per school, not per parent
   ([ChildController.php:37-165](../app/Http/Controllers/Api/ChildController.php:37)).
4. **Self-signup ignores `max_limit` entirely** — `RegisterService` never reads it. So even the parent cap only holds on
   the import path.

The overflow is silent in both directions: over-cap rows are `array_slice`d away with no message, and the success
banner reports only the inserted count.

Layered on top, the column `max_limit` **has no migration** — `create_schools_table`
([:14](../database/migrations/2025_02_20_110120_create_schools_table.php:14)) creates only
`id, name, school_code, user_id, status, timestamps`. Production has been hand-patched; `migrate:fresh` breaks the
feature. The migration in §5.1 closes this as a side effect.

### 3.2 The semantic decision

"The school pays for 200 child registrations" means **the billable unit is the child**, while `max_limit` today caps
*parents*. These are different numbers and both are needed — a school with 200 child seats may name 150 parents.

**Do not redefine `max_limit`.** Repointing it at children would silently change the importer's behaviour for every
existing school on deploy, which is exactly the regression this plan forbids. Instead:

| Column | Meaning | Status |
|---|---|---|
| `schools.max_limit` | max **parent/roster** accounts | existing, untouched |
| `schools.child_seat_limit` | max **child** accounts (the billable seat) | **new**, nullable |
| `schools.per_parent_child_limit` | max children per parent within the school | **new**, nullable |

`child_seat_limit = NULL` means unenforced — today's behaviour. Enforcement begins the moment an admin sets a number,
per school, which is the rollout control.

---

## 4. Child seat design

### 4.1 Counting: derive, do not denormalise

The obvious implementation is to copy `school_id` onto the child row at creation. **Do not.** It changes the result of
every query in §1.2 at once — most damagingly `User::where('school_id', ...)->count()` in the importer, which would
start counting children against the *parent* cap and break imports for every existing school on the first deploy. It
would also change `whereNull('school_id')` in `/api/login` ([:400](../app/Http/Controllers/Api/HomeApiController.php:400))
and school-scoped content filtering.

Use the join from §1.2 instead. It is correct today, needs no migration, and cannot change the behaviour of any
existing query. Add a covering index on `users (parent_id, user_role_id, deleted_at)` and on `users (school_id)` —
neither exists beyond the FK.

### 4.2 Enforcement: row lock inside the transaction that already exists

`addChild` already wraps the child insert in `DB::transaction`
([ChildController.php:90-115](../app/Http/Controllers/Api/ChildController.php:90)). The check goes inside it, gated on
a `lockForUpdate()` of the school row so two concurrent requests cannot both pass the count. A single source of truth,
no counter column, no drift:

```php
$child = DB::transaction(function () use (...) {
    $parent = User::find(Auth::id());

    if ($parent->school_id) {
        $school = School::whereKey($parent->school_id)->lockForUpdate()->first();
        $seats->assertChildSeatAvailable($school, $parent);   // throws SeatLimitException
    }

    $child = User::create([...]);   // unchanged
    BatteryEvent::create([...]);    // unchanged
    return $child;
});
```

`$parent->school_id` being null is the consumer-parent case — the guard is skipped entirely and that path is provably
untouched.

`assertChildSeatAvailable` checks, in order: `per_parent_child_limit` (cheap, no school-wide scan), then
`child_seat_limit` against the §1.2 count. Each failure carries its own message so the parent knows whether to remove
one of their own children or contact the school.

### 4.3 Grandfathering and lowering the limit

A school may already be over any limit an admin now sets. The rule is **never retro-delete**: enforcement applies only
to *new* creations. On the admin side, setting `child_seat_limit` below current usage should be permitted but must show
the current count and warn that existing children are retained and no new ones can be created until usage falls below
the limit. The same applies when `max_limit` is lowered — which today does nothing at all to existing users.

### 4.4 Seat release

`deleteChild` soft-deletes ([ChildController::deleteChild](../app/Http/Controllers/Api/ChildController.php)), and the
count in §1.2 excludes `deleted_at IS NOT NULL` — so a deleted child frees a seat automatically with no extra code.

This is a **product decision to confirm**, because it is also an abuse vector: a school at capacity could cycle children
indefinitely, and a paid-per-seat model may intend a seat to be consumed on first use. Two options:

- **(a) Free on delete** *(recommended, and what the code does by default)* — simplest, most forgiving, matches parent
  expectations. Pair with a light guard: log releases, and optionally a cooldown before the freed seat is re-usable.
- **(b) Seat consumed on allocation** — requires a `school_seat_events` ledger (school, parent, child, allocated_at,
  released_at, reason) and counting allocations rather than live children.

Option (b) is worth building regardless as an **audit table**, even if the count stays live — it is what answers "who
used our 200 seats and when" for the school, and it is the natural source for a seat-usage report. The recommendation
is: ship (a) for enforcement now, add the ledger as an append-only audit log in the same phase, and keep (b) available
as a config switch if the commercial model demands it.

### 4.5 Visibility — the part that is missing everywhere today

Seat state must be readable by all three audiences, or the limit just produces confusing errors:

- **Parent (app):** add a `seats` block to `getProfile` — `{ limit, used, remaining, per_parent_limit }`, null for
  consumer parents. Purely additive, so no client breaks. The app can then disable "Add child" *before* the failed call.
- **Admin panel:** child seats used / limit on the school view and index, alongside the existing `max_limit` display
  ([view.blade.php:41](../resources/views/admin/schoolManagement/view.blade.php:41)); a roster tab listing invite status
  and per-parent child counts.
- **School:** a `school_seat_limit_reached` notification when usage crosses a threshold (90%) and again at 100% —
  this is item 4 in the email inventory of the companion review, and is what turns a hard stop into an upsell.

---

## 5. Implementation plan

Ordered so that each phase is independently deployable and each leaves the system working.

### Phase 0 — Schema (no behaviour change)

One migration, `Schema::hasColumn`-guarded throughout, matching the style of
`2026_09_11_090000_align_schema_and_admin_otp_template.php`:

1. Add the hand-patched columns if missing: `schools.max_limit`, `schools.subscription_type`, `schools.email`. Closes
   the `migrate:fresh` break in §3.1.
2. Add `schools.child_seat_limit` (nullable), `schools.per_parent_child_limit` (nullable),
   `schools.enforce_parent_roster` (bool, default **false**), `schools.self_signup_enabled` (bool, default **true**),
   `schools.code_rotated_at` (nullable).
3. Create `school_parent_invites` (§2.2).
4. Indexes: `users (school_id)`, `users (parent_id, user_role_id, deleted_at)`.

Defaults are chosen so that a deploy of Phase 0 alone is a no-op.

### Phase 1 — Roster backfill (no behaviour change)

An idempotent, re-runnable command `php artisan school:backfill-roster [--school=]` that, for every school, inserts a
`claimed` invite row for each existing `users` row with `school_id = X` and `user_role_id = 3`, setting
`claimed_user_id`. Report per school: parents found, invites created, invites already present.

This must run and be verified **before** any enforcement flag is turned on — otherwise every existing school parent
becomes off-roster.

### Phase 2 — Services (no call sites yet)

- `app/Services/School/SchoolRosterService.php` — `findClaimable(School, string $email): ?SchoolParentInvite`,
  `claim(SchoolParentInvite, User): void`, `revoke()`, `invite()`.
- `app/Services/School/SchoolSeatService.php` — `childSeatsUsed(School): int`, `remaining(School): ?int`,
  `assertChildSeatAvailable(School, User $parent): void`.
- `SeatLimitException`, `RosterRejectionException` mapped to the existing `ApiResponse::error` envelope, preserving the
  current status-code conventions per endpoint (v1 quirks included).

### Phase 3 — Wire in child seats

`ChildController::addChild` only (§4.2), plus the `seats` block in `getProfile` and the admin display. Inert until a
school has a `child_seat_limit`.

### Phase 4 — Wire in the roster

`RegisterService::register` (with the transaction it currently lacks), `updateParentProfile`, and the import writing
roster rows. Inert until `enforce_parent_roster` is set. Fix `RegisterService:74` in this phase — the roster is not
sound without it.

### Phase 5 — Admin surface

Roster tab (list, invite, revoke, resend), seat usage display, the `enforce_parent_roster` / `self_signup_enabled`
toggles, code rotation, and the over-limit warning when lowering a cap.

### Phase 6 — Hardening and notifications

`studentLogin` rewrite, register validator rules, verified-email requirement, seat-threshold and roster-rejection
notifications. Note the email-template caveat from the companion review: `___mail_sender`
([helper.php:66-69](../app/helper.php:66)) silently returns when a template is missing, and
`EmailTemplateController::create()/store()` are empty stubs — so any new template needs a seeder **and** a Blade
fallback, or it will never send.

### Rollout

Per school, in order: backfill → verify roster count matches parent count → set `child_seat_limit` → observe →
set `enforce_parent_roster` → optionally disable `self_signup_enabled`. Every step is individually reversible by
clearing the column.

---

## 6. Decisions needed before Phase 3

These are commercial, not technical, and each changes what gets built:

1. **Seat unit** — assumed **child**, per "school pays for 200 child registrations". Parents remain capped separately by
   `max_limit`. Confirm.
2. **Does deleting a child free a seat?** Assumed **yes** (§4.4a). If seats are consumed on allocation, Phase 3 needs
   the ledger as the counting source, not just as audit.
3. **Per-parent child cap** — assumed **unlimited within the school cap** unless an admin sets one. A default (3?) is
   worth agreeing, since it is the practical defence against one parent draining the school's seats.
4. **Off-roster self-signup behaviour** — assumed **hard reject**. The alternative is to degrade to a normal consumer
   signup (no `school_id`, no free subscription), which is friendlier but silently changes what the parent thinks they
   bought. Reject is recommended; degrade is defensible.
5. **Teachers and `max_limit`** — they currently consume parent seats (§3.1.1). Assumed **a bug**; the fix is to scope
   the count to `user_role_id = 3`. This *does* change existing import behaviour (schools gain back seats teachers were
   eating), so it needs explicit sign-off rather than being slipped in.
6. **Existing over-limit schools** — assumed **grandfathered**, never retro-deleted (§4.3).

---

## 7. Verification

- `php artisan migrate:fresh --seed` completes and school create/edit works end to end (proves the Phase 0 schema fix).
- Backfill on a copy of production: roster row count per school equals `users` parent count per school, exactly.
- **Flags off:** full regression pass on register (with and without code), student-login, add/edit/delete child, admin
  import, profile update. Every response identical to pre-change — this is the no-regression gate and should be a
  snapshot diff, not a manual check.
- **Roster on:** register with (a) a roster email — succeeds and claims; (b) the same roster email again — rejected;
  (c) a non-roster email with a valid code — rejected with the generic message; (d) an unverified pre-existing account
  at a roster email — cannot be taken over.
- **Seats on:** with `child_seat_limit = N`, create children to N (succeed), N+1 (rejected with the school message);
  delete one, create again (succeeds under option a); exceed `per_parent_child_limit` on one parent while the school
  has room (rejected with the per-parent message).
- **Concurrency:** two simultaneous `add-child` calls at exactly one remaining seat — one succeeds, one is rejected.
  This is the test that proves the `lockForUpdate`; without it the check is racy under real load.
- **Consumer parents** (`school_id` null): unaffected on every path.
- Feature tests: `SchoolFactory` does not exist yet and must be added first — there is currently **zero** test coverage
  for anything school-related.
