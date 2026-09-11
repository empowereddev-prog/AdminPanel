# Mobile API Remediation

Status of the work to make the 71 mobile API endpoints correct and safe, without
breaking the shipped mobile app.

- **Branch:** `phase0-api-hotfix` (branched from `sprint1_dev`; the name predates
  Phases 1 and 2 — worth renaming to `api-remediation` before opening a PR)
- **Last updated:** 2026-09-11
- **Tests:** 51 passing, 340 assertions, 0 skipped
- **Phases 0 and 1 complete. Phase 2 in progress: 4 of 6 controllers migrated.**

---

## Context

`routes/api.php` exposes 71 mobile endpoints across 12 `Api/*` controllers plus
three shared web+API controllers. The surface grew by copy-paste and had no
shared foundation:

- **No response contract.** `Api/ResponseController` existed but was called from
  two lines and could not express an error at all — every method hardcoded
  `response()->json($response)` with no status argument. Everything else
  hand-rolled its own envelope.
- **No error contract.** The exception handler did not force JSON for `api/*`, so
  a client omitting `Accept: application/json` got a 302 redirect from
  validation, an HTML page from `findOrFail()`, and a redirect from throttling.
- **Authorization was advisory.** Routes were behind `auth:api`, but handlers
  then acted on whatever `user_id`/`child_id` the request body carried.
- **The schema did not match the code.** Children are created as `users` rows,
  but no migration ever added the columns that requires. `addChild` failed
  outright on a database built from this repo.

The constraint throughout: **additive only**. The shipped app must keep receiving
the same status codes and the same keys. Corrected behaviour that would change
the wire format goes behind an opt-in `Accept-Version: 2` header.

---

## Done

### Phase 0 — Security hotfix (`313b299`)

Authentication holes, each verified in the source before fixing:

| Fix | What it allowed |
|---|---|
| `reset()` resolves its target from the token | Changing **any** account's password via a body `user_id`, with no old-password and no ownership check |
| Removed the `otp == '1111'` branch | Verifying any registered phone number |
| Teacher login passes `user_role_id` to `attempt()` | Any child or parent logging in through the teacher endpoint |
| Re-enabled `validateGooglePlayReceipt()` | Android subscriptions skipped receipt verification and trusted client-supplied `transaction_id`, `price`, `end_date` |

IDOR: added `App\Http\Controllers\Concerns\ResolvesApiUser`, which resolves the
acted-on user from the bearer token and honours a body id only for self or own
child. Applied across notifications, moods, watch history, avatars, child
management and the content feeds. The deliberate teacher cross-child access in
`videoContentforchild` was preserved explicitly.

Removed two endpoints: `send-notification` (any user could push-blast every
device token in the system) and `test-battery` (debug endpoint hardcoded to user
425).

Correctness: `QuizController` called an undefined `between()`, making every
age-ranged quiz question a 500; `userUnlockAvatars` accepted negative points that
passed the balance check and *credited* the account; `storeAvtar` wiped
`users.avtar_image` on any save without a file; watch time was summed from the
wrong variable; `is_account_active` was inverted relative to the login age gate.

Also hid `otp`, `mobile_otp` and `password_reset_code` from `User` serialisation
— they leaked from every endpoint returning a raw model.

### Phase 1 — Foundation (`8fd6fed`, `c2fb3b9`)

- **`app/Exceptions/Handler.php`** now routes `api/*` through a JSON renderer.
  Status codes match what Laravel already returned to clients sending `Accept`
  (422/401/403/404), so only the broken no-`Accept` path changed. Exception
  messages are no longer leaked unless `app.debug`.
- **`app/Support/ApiResponse.php`** — the envelope (`status`/`message`/`data`,
  optional `errors`). `$legacy` carries v1-only aliases; `$extra` carries keys
  that must survive into v2 (the auth token). Neither can overwrite a canonical
  key. `ApiVersion` reads `Accept-Version`.
- **`ApiCheckStatus`** read `auth()->check() && ...`, so unauthenticated requests
  passed straight through — a no-op gate that only worked because of middleware
  ordering. Now resolves `auth('api')` explicitly and folds in the
  school-inactive rule that was duplicated in two controllers and enforced
  nowhere else.
- **Transactions** on the five unprotected multi-write paths. The worst was
  `updateBatteryAndLoyalty`, which marked watch rows consumed *before* awarding
  points and only re-reads unconsumed rows — a mid-loop failure lost them
  permanently.
- **`issueToken()`** replaced three drifted copies of the login token block,
  removing a dead `$tokenIds` loop and three redundant writes per login.
- **`App\Http\Requests\Api\ApiFormRequest`** — base whose `failedValidation` and
  `failedAuthorization` render the envelope.

### Schema alignment (`65457dc`, extended in `c1e1fb2`)

Two guarded, idempotent migrations. Verified they round-trip and re-run as
no-ops, including against a database already patched by hand.

`users`: added `username` (unique), `dob` (`varchar(7)`, `YYYY-MM`),
`loyalty_points` (`decimal(10,2)`, cast to float so it serialises as a number),
`avtar_image`, `is_first_login`, `is_avatar_primary`, `is_mood_updated`. Added
`'child'` to the `user_type` enum. Converted `is_mobile_verified` from
`enum('0','1')` — which rejected every write the app made — to `enum('yes','no')`,
translating existing rows.

Supporting tables: `child_moods.date` (backfilled from `created_at`),
`moods.type`, `user_content_watch_histories.is_completed`,
`user_attempt_quizzes.user_id`, `email_templates.language`, `faqs.type`.

Found by smoke-scanning all endpoints for 5xx — 10 were failing. Fixed:
`individualLogin` read `$language` in its catch before assigning it (any bad
payload was a 500), `childSupport` dereferenced a null parent, `logout` called
`token()->revoke()` with no null guard, `storeChildMood` hit a not-null
constraint on empty input.

**Also fixed a regression introduced in Phase 1:** the handler intercepted
`api/*` before `parent::render()`, so `HttpResponseException` — what every
`FormRequest::failedValidation` throws — became a 500. Three tests pin it.

### Phase 2 — In progress

**The gate (`c1e1fb2`, `0eb6e73`) — build this understanding before continuing.**

`tests/Feature/Api/ResponseContractSnapshotTest.php` records all 68 endpoint
cases and enforces the additive-only contract: **status codes exact, body keys a
recursive subset** — nothing removed, renamed or retyped; additions pass.

Regenerate only after reviewing a diff:

```bash
UPDATE_API_SNAPSHOTS=1 php vendor/bin/phpunit tests/Feature/Api/ResponseContractSnapshotTest.php
```

> The first version of this gate did not work. `status:true` and `status:false`
> both collapsed to `<bool>`, so an endpoint falling into its catch block looked
> identical to one succeeding — it passed a deliberately injected canary.
> Booleans now keep their value. A second blind spot surfaced during the
> `KnowledgeBaseController` pass: assoc decoding collapsed `{}` and `[]` into the
> same `<list:empty>` signature. Objects are now signed as `<object:empty>`.
> **If you extend the signature, re-verify it with a canary before trusting it.**
> The first blind spot was also hiding the `faqs.type` bug. A third problem —
> the snapshot silently going stale with the calendar — turned up in the
> `MoodTrackerController` pass. Three defects in three passes: assume there are
> more, and prefer verifying a property to trusting it.

**`HomeApiController` migrated (`98f01c4`).** 56 live hand-rolled blocks → 3,
behind 58 `ApiResponse` calls, gate green throughout. Fixed three error-path
bugs: `register`'s catch reported failures as `status: true` while leaking SQL;
`passwordReset` referenced a `$language` never assigned anywhere in the method,
so a *successful* reset raised `ErrorException`; `resetPassword` type-hinted a
class it never imported.

**`ChildController` migrated.** 17 hand-rolled `response()->json` blocks and 4
bare-array envelope returns → 0 live blocks, behind 21 `ApiResponse` calls, gate
green. The transformer converted 7 and refused 10; the refusals were the ones
that mattered:

- `addChild`/`editChild` success return `child` with **no `data` key at all** —
  migrated as `data` plus `child` in `$legacy`.
- `updateBatteryAndLoyalty` returns `battery_percentage`/`earned_points` flat.
  They go in `data` *and* `$legacy`, so v2 keeps them rather than losing them.
- `primaryChild`'s not-found branch is `data => null` — **left unmigrated and
  annotated**, same call as `HomeApiController`. The snapshot confirms it:
  `primary-child:unknown` records `"data": "<null>"`.
- `deleteChild` returned bare arrays, which Laravel serialised at 200. Kept at
  200 — the shipped app reads status, not the code.

Validation stays inline in `addChild`/`editChild` rather than moving to
FormRequests: both answer a validation failure with **201**, and `ApiFormRequest`
renders 422. Moving them is a client-release change, not an additive one.

Error-path fix: `getProfile` read `$user->language` one line *above* its own
`!$user` guard, warning on every unknown id. Now `$user?->language`.

**The gate was canaried on this controller**, per the warning below: dropping the
`$legacy` array from `updateBatteryAndLoyalty` produced
`child-percentage.battery_percentage: key removed or renamed`. It is live here.

Covered by snapshot cases: `add-child:invalid`, `edit-child:invalid`,
`delete-child:unknown`, `primary-child:unknown`, `get-profile`,
`parent-dashboard`, `child-percentage`, `subscription:invalid`. Not reachable
without fixtures, so unverified by the gate: the success paths of `addChild`,
`editChild`, `deleteChild`, `primaryChild` and `subscription`, and `getProfile`'s
404/age/school branches. Each of those changes is additive by construction — a
`data` key added — but that rests on reading, not on a test.

**`KnowledgeBaseController` migrated.** 17 live blocks → 4, all four of them
deliberate `data => null` contracts, annotated in place. The transformer
converted 12; the refusals were the two `data => null` fallthroughs, the
one-line `Missing IDs` 400, and both deep-link error blocks, which carry
`deeplink_status`/`canonical_url` at top level *and* `data => null` — the
snapshot pins that exact shape as `video-content-details`.

**Two tooling defects were found and fixed doing this — read these before the
next controller.**

*The transformer could skip a block without reporting it.* When the status-code
argument was not a literal (`], $deepLink['http_status'])`), the tail regex
failed and the block fell through to the no-op branch — counted as neither
converted nor skipped. A refusal-based tool that can refuse silently is worse
than no tool: the block would simply have been forgotten. It now reports
`could not parse the status-code argument`, and every skip carries a line
number. Re-checking `HomeApiController` and `ChildController` against the fixed
tool found nothing else hidden.

*The gate could not tell `{}` from `[]`.* The snapshot decoded responses with
`json_decode($content, true)`, so a JSON object and a JSON array both arrived as
`[]` and both signed as `<list:empty>`. That is precisely the distinction this
migration turns on — it is why `data => null` blocks are left alone — and
retyping `data: []` to `data: {}` passed a canary. To a typed mobile client the
two are not interchangeable: `{}` decoded into a `[Video]` fails outright.
`ResponseSignature` now keeps objects as objects and signs an empty one as
`<object:empty>`; the same canary fails correctly.

The snapshot was regenerated for that change **with the controller migration
stashed**, so the signature fix could be reviewed on its own. Every delta was
either a `<list:empty>` → `<object:empty>` retype (18 cases that were always
`{}`) or a `data` key added by the already-merged `HomeApiController` and
`ChildController` work (6 cases). **No key was removed and no status code
moved.**

Gate-covered here: `video-content`, `video-content-quiz`,
`video-content-details`, `video-content-for-parent`, `video-content-for-child`,
`video-watch-status`, `user-content-watch-histories:invalid`. Unverified without
fixtures: the success paths of `videoContent`, `videoContentQuiz` and
`videoContentdetails`, and `videoContentforchild`'s two access-denied branches.

**`MoodTrackerController` migrated.** 19 live blocks → 4. This one is a shared
web+API controller, so the first step was mapping every block to its route:
all 19 sat in the nine API methods, and the admin methods return views, so
nothing web-facing was touched. Check that before editing the two remaining
shared controllers.

Two shapes needed a decision:

- `getAllMood` returns `negativeStatus`/`consecutiveDays` flat and has **no
  `message` key at all**. They went in `$extra`, not `$legacy` — they are
  payload the app reads, not aliases of a canonical key, so a v2 client must
  keep receiving them. This is the second use of `$extra` after the auth token.
- `moodTracker` returns `calendar_data`/`mood_ring`/`top_emotions`/`categories`
  flat with no `data` key. All four now sit in `data` and are mirrored flat for
  v1.

Left hand-rolled: three `data => null` branches, plus `getAllMood11` — a dead
method no route points at (`get-all-mood` routes to `getAllMood`). Annotated as
dead rather than migrated; migrating dead code only makes it look maintained.
It is ~60 lines and a near-duplicate of `getAllMood`, worth deleting on its own.

**The gate had a time bomb, now defused.** `mood-tracker`'s `calendar_data` is
keyed by the days of the current month, so the snapshot pinned the 30 days of
September 2026. Travelling the clock one month forward produced **60 spurious
violations with no code change** — verified, not assumed. A gate that cries wolf
gets regenerated reflexively, which is exactly how a real break gets waved
through. The snapshot test now freezes the clock at `2026-09-15` (inside the
recorded month, so no regeneration was needed) and clears it in `tearDown`.
`moodTracker` reads `Carbon::now()`, so the freeze genuinely reaches it.

Gate-covered here: `get-all-mood`, `get-child-mood`, `mood-tracker`,
`activity-list`, `child-support`, `get-suggested-activity`,
`store-child-mood:invalid`, `store-activity:invalid`,
`store-liked-content:invalid`. Unverified without fixtures: the success paths of
`storeChildMood`, `storeChildActivity`, `getSuggestedActivity` and
`storeLikedVideoContent`, and `childSupport`'s two mail-sent branches.

### Deep links (`d576817`)

`/deeplink/resolve` returned `canonical_url` of `http://13.229.56.31/d/article/154`.
Those links can never open the app — Universal Links and App Links require https
on a domain verified through `.well-known`.

Two causes: `config/deeplink.php` fell back to `APP_URL` (removed), and
`canonical_url` is **persisted, not computed** — the backfill migration baked the
wrong base into the table, and both `DeepLinkService::resolve()` and the model
accessors prefer the stored value. Fixing the environment alone does not fix
existing rows.

Added `deeplink:rebase-canonical-urls`, which refuses to run when the configured
base is itself unusable (bare IP, localhost, plain http), since re-baking a bad
base is the failure being fixed.

---

## To do

### 1. Finish Phase 2 — controller migration

~55 hand-rolled `response()->json` calls remain in unmigrated controllers. Order by traffic:

| Controller | Remaining |
|---|---|
| `QuizController` | 17 |
| `KnowledgeSessionController` | 8 |
| `AvtarController` | 8 |
| `ResponseController` | 6 |
| `NotificationController` | 5 |
| `FaqController`, `PopupLoginController`, `UserArticleLikeController`, others | ~11 |

The four migrated controllers still hold 12 blocks between them: 11 deliberate
`data => null` contracts left for a client release, and one dead method. They
are annotated as such in the source; do not "finish" them without one.

`QuizController` and `NotificationController` are also shared web+API
controllers. Map each block to its route before touching it, as
`MoodTrackerController` required — an admin AJAX response is not on the mobile
contract and is not covered by the gate.

Per controller: swap responses for `ApiResponse::*` keeping v1 keys, move
validation into FormRequests, fix error-path bugs, run the gate.

**Reuse the transformer**, now committed as `scripts/migrate_envelope.py`
(it previously lived only in a scratchpad). It converted 48 of 56 blocks in
`HomeApiController` and 7 of 17 in `ChildController` and, importantly, *refused*
the ambiguous ones —
including three whose contract is `data: null`, which `ApiResponse` would render
as `{}`. Have it report what it skips and handle those by hand. Do not
bulk-rewrite without the gate green after each pass.

Two shapes need conscious decisions each time:
- `data => null` — converting changes the type the app receives. Leave it and
  annotate, as in `HomeApiController`.
- Responses carrying `token` — use `$extra`, not `$legacy`, or v2 clients
  silently lose their token.

### 2. Language extraction — sequence separately

~130 `$language == 'english' ? 'A' : '中文'` ternaries should become `trans()`
with `lang/en` + `lang/zh`. **The gate cannot verify this** — it checks shape,
not message text.

Do it as a mechanical pass that generates the lang files *from the existing
pairs*, so text cannot drift. Several pairs are already mismatched and need
deciding, not copying: `HomeApiController` pairs "Email doesn't Exists." with
"您的帐户已成功删除。" ("account deleted successfully"); `ChildController` pairs
"Data not found." with "数据更新成功。" ("update successful"). `editChild`
hardcodes `$language = 'english'`, making its Chinese branch unreachable.

### 3. Phase 3 — Performance (not started)

- **Real pagination.** `KnowledgeBaseController:406` loads the entire result set
  then `forPage()`s it in memory, so DB cost is independent of `page`. `per_page`
  is uncapped — `per_page=100000` is accepted.
- **N+1**: 2 `Category` queries per video/session across the content feeds;
  `parentDashboard` runs a nested loop (~75 queries for 3 children × 12
  categories); `QuizController:1597` runs a query per question and *discards* the
  result (`'is_attempt' => $attempt ? 'no' : 'no'`).
- Remove `->useWritePdo()` from two read paths; `MeetTeamController` orders by a
  nested `REPLACE()` expression that forces a filesort.

### 4. Deferred with a reason

- **`primaryChild` still uses the `Child` table.** `is_primary` exists only
  there, so unifying onto `User` needs a migration and a backfill decision. Its
  security bug is fixed; the model choice is not.
- **Passport tokens never expire** — nothing configures `tokensExpireIn`. Setting
  it invalidates every live token at once, so it needs a rollout decision.
- **`moods.type` is nullable and unpopulated.** No default was guessed, because a
  wrong guess silently misclassifies existing moods. Someone must classify the
  rows as positive/negative or the negative-mood streak logic stays inert.

---

## Environment notes

- **This dev database has no Passport personal access client**, so
  `createToken()` fails locally and login flows cannot be exercised end to end.
  Fix with `php artisan passport:client --personal` — left undone because
  creating an OAuth client is a credential operation on your database.
- **`DEEPLINK_ANDROID_SHA256` is empty** in `.env` and `.env.example`. Without
  the signing-certificate fingerprint, `assetlinks.json` cannot verify Android
  App Links even once the domain is correct.

## Production runbook — deep link repair

Order matters; the command's guard will stop you otherwise, which is intended.

```bash
# 1. Set DEEPLINK_PUBLIC_BASE_URL=https://admin.empoweredhealth.asia and fix APP_URL
php artisan config:clear && php artisan config:cache

# 2. Inspect, then apply
php artisan deeplink:rebase-canonical-urls --dry-run
php artisan deeplink:rebase-canonical-urls
```

## Commands

```bash
php vendor/bin/phpunit                                  # 51 tests
php vendor/bin/phpunit tests/Feature/Api                # API suites only
php artisan migrate                                     # both alignment migrations are idempotent
```
