# School safety feature — app integration

Admin Add/Edit School has “Does the school need the safety feature?” with Yes/No.
The database field is `schools.needs_safety_feature` (`yes` / `no`), default `yes`.
Only Admin may change it. Existing schools and individual accounts retain enabled
behavior by default. The setting applies to linked children through the parent's
school, and directly to independent students, school parents and teachers.

## After login

```http
GET /api/school/safety-feature
Authorization: Bearer <access_token>
Accept: application/json
```

```json
{
  "status": true,
  "message": "School safety feature setting retrieved.",
  "data": {
    "school_id": 1,
    "needs_safety_feature": "no",
    "safety_feature_enabled": false,
    "show_get_help": false
  }
}
```

No school_id or user_id parameter is required: the server derives membership
from the authenticated user, so the app cannot substitute another school's id.
For individual accounts, school_id is null and the three visibility values are
yes/true/true. Linked children inherit their parent's setting. Independent
children use their own school_id.

Use `show_get_help` to show/hide Get Help in both the sidebar and bottom navigation.
Use `safety_feature_enabled` for the designated safety feature/related alert UI.
Keep policy verification and consent flows intact. This API is a UI setting;
it does not remove existing support endpoints or bypass their permissions.

Fetch on login and when the app resumes / refreshes school settings, so Admin
changes appear without reinstalling the app. Do not store a permanent stale flag.
Handle authentication/network errors separately from a successful `no` response.

## Before login

The existing `POST /api/school/account-mode` school-code lookup also returns
`needs_safety_feature` and `safety_feature_enabled`. Use this for school-code
onboarding if needed; use the authenticated GET for the current account's setting.

Frontend app code is outside this repository; the dropdown, persistence and API
are implemented here, while actual app visibility needs this integration.
