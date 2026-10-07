# School account creation mode — frontend guide

## Meaning

Every school has one configured `account_mode`:

- `linked`: Parent-linked Child Accounts. Existing school-code parent signup
  and parent import remain available; children are linked through `parent_id`.
- `independent`: Independent Child Accounts. School Management imports students
  directly, with `school_id` set and `parent_id=null`. No parent is created.

Only Admin (role 1) with School Management modify permission may change the mode.
Sub-admin cannot change it via UI or a forged request. Changes control future
creation, never convert or unlink existing accounts. A school may therefore
contain both historical linked children and independent children after a change.
Parent/Child/Both was the earlier interpretation and is superseded by these two
relationship-based modes. Existing school values are migrated to linked or
independent without altering any user records.

## Admin flow

School Add/Edit → Account Mode → save.

Linked mode uses the existing parent onboarding. Independent mode hides parent
upload, parent limit and children-per-parent fields. After saving the school,
open School Details → Import Students → Download student template → Upload.

Required spreadsheet columns (exact order):

`Name | Email | Username | Date of Birth (YYYY-MM)`

Use unique usernames (3–100 characters, letters/digits/underscore/dot/hyphen),
valid emails, names of 3–50 characters, and students under 18. Maximum 1000 rows
and 5 MB per upload. Invalid or duplicate rows reject the entire upload; no partial
accounts. Existing credentials are never reset by uploading an existing student.

The school receives a spreadsheet with temporary passwords and web reset links
valid for 24 hours. An authorized download is also offered after import. Files
are stored on the private local disk, not in public uploads. If SMTP fails,
accounts remain created and the download is still available.

The Child Accounts list includes both creation types with an Account Relationship
column. Linked students show their parent; independent students show No parent
link. Counts include both, regardless of the current school setting.

## School-code frontend lookup

```http
POST /api/school/account-mode
Content-Type: application/json
Accept: application/json

{"school_code":"SCH-499858"}
```

Example independent result:

```json
{
  "status": true,
  "message": "School account mode retrieved.",
  "data": {
    "account_mode": "independent",
    "parent_signup_allowed": false,
    "child_creation_mode": "school_import"
  }
}
```

Linked returns `account_mode=linked`, `child_creation_mode=parent_linked`.
`parent_signup_allowed` additionally respects the existing school signup switch.
Roster/email validation still happens at signup; the lookup is not authorization.
Invalid/inactive code returns HTTP 404. Validation uses HTTP 422; rate limit is
30 requests/minute. Fetch again when the entered school code changes.

Linked: retain parent signup and linked child workflows. Independent: display
“Your school creates your student account. Use the credentials provided by your
school.” Offer the existing child username/password login (`POST /api/login`,
`type=child`). Do not use the legacy `student-login` endpoint for these new
accounts: that endpoint authenticates school parents by email.

For recovery use `/api/forgot-password` with email, type=child and username where
needed, or the web link from the credentials spreadsheet. APP_URL must point to
the reachable backend so reset links work. Local links use 127.0.0.1:8000; use the
real HTTPS domain for deployment.

Do not hide linked-parent features for existing users simply because their school
mode changed. Use the actual relationship (`parent_id`) for an existing child's
account UI. Individual accounts retain their current workflows.

The mobile frontend is outside this repository; these instructions describe the
API integration its developer should implement.
