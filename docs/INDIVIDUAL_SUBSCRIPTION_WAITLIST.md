# Individual subscription waitlist

## Implemented scope

- Homepage section immediately after the existing Elementor Key Features container (`cb707a0`).
- Original EmpowerED imagery, blue/pink/aqua/cream colours, Roc Grotesk headings and Poppins body text.
- No "Made for you!" tagline, launch notification promises, public landing page, account creation, payments or outbound emails.
- Email-only signup with a privacy link, validation, duplicate feedback and success/error feedback.
- Separate Laravel table, unique normalized emails, a honeypot, and IP throttling (5/minute and 30/hour).
- Existing Laravel admin navigation and permission picker integration, a searchable paginated list, registration date/source and an unfiltered unique email count.
- Superadmins can view the module; other active administrators need an explicit `is_view = yes` permission for its menu entry. This release does not grant permissions to existing subadmins.
- No CSV export or deletion tools.

## Status

The owner requested frontend publication and a Git push, but **no backend deployment**. The homepage section is published using Elementor Custom Code, named `EmpowerED — Individual Subscription Waitlist`, restricted to **Front page** at **Body End**. WordPress plugin upload required unavailable FTPS credentials, so no new plugin was installed. The snippet is tracked at `integrations/wordpress/elementor-homepage-waitlist.html`.

The published snippet deliberately has `REGISTRATIONS_OPEN = false`: the design is visible, its inputs/button are disabled and it displays `Registration opens soon. Please check back shortly.` No production migration has been run and no live waitlist emails are being collected. Backend code is pushed on the existing task branch, without merging or deploying to the server.

The configured local environment uses MySQL on loopback port 3307. The guarded attempt to apply only this feature migration was refused by that local database port (no MySQL server was listening). No local application-database migration was applied. Start the existing local MySQL environment before applying the feature migration; do not substitute a remote/production database to work around this. The migration itself passed the isolated SQLite feature suite.

## Backend API

`POST /api/individual-subscription-waitlist`

JSON body:

```json
{"email":"visitor@example.com","website":""}
```

`website` is a honeypot and must remain empty. The server records `source = homepage`; clients cannot override it.

Responses:

- `201`: `success: true`, `status: registered`, and an on-screen confirmation message.
- `200`: `success: true`, `status: already_registered`; no second row or count increment.
- `422`: Laravel API validation error; no row is stored.
- `429`: Too many requests; the frontend asks the visitor to wait.
- Other failures: the frontend retains the email and displays a retry message. It does not claim that a failed request saved anything.

No authentication cookie or API secret is sent by the public website. Existing Laravel CORS configuration already covers `api/*`. No app/mobile routes were altered.

## Admin

`GET /individual-subscription-waitlist` requires the existing admin session, active-account middleware and the module's view permission. Normal requests render the existing admin layout. JSON requests return the same authorized paginated data and `total_unique`; they are not a public directory.

## Local verification

PHP 8.4.27 is available next to this checkout. From the repository root:

```powershell
& '..\php-8.4.27\php.exe' vendor/phpunit/phpunit/phpunit tests/Feature/IndividualSubscriptionWaitlistTest.php --do-not-cache-result
```

The focused suite creates its own in-memory SQLite connection. It does not migrate the database configured in `.env` and uses fake mail. Seven tests cover public signup, no account/email side effects, normalization and duplicates, invalid/spam submissions, throttling, guest protection and explicit subadmin permissions. Frontend success and duplicate handling were checked separately with a local in-memory test endpoint, using only `example.test` addresses. Desktop and 390px mobile layouts were visually inspected.

## Future release (not executed)

1. Review the source on the existing task branch. The checkout is based on `main`; the older deployment runbook refers to `sprint1_dev`. Confirm the actual release branch instead of merging unrelated branch differences or assuming automatic deployment.
2. Back up the server/database and deploy the reviewed Laravel release using the established server release process. See `docs/DEPLOYMENT.md`; do not run `config:cache` for this application.
3. Apply only this feature's migration after reviewing pending migrations:

   ```bash
   php artisan migrate --path=database/migrations/2026_10_30_150000_create_individual_subscription_waitlist.php --force
   php artisan route:clear
   ```

4. Verify the endpoint from the marketing website's origin, including invalid email, duplicate submission, throttling and backend failure. Use a designated test address and verify one row in the authorized admin list. The count is unique email addresses, not verified people or paid subscriptions.
5. After backend deployment, update the existing Elementor Custom Code snippet's `REGISTRATIONS_OPEN` constant to `true`, using the tracked HTML source, and keep **Front page / Body End** conditions. The production endpoint is `https://admin.empoweredhealth.asia/api/individual-subscription-waitlist`.
6. Alternatively, if replacing the native snippet with the packaged plugin later, unpublish the snippet before activating the plugin. The plugin has separate **Show section** and **Accept registrations** settings under **Settings > Subscription Waitlist**, both disabled by default. Keep **Accept registrations** off until the backend is verified. Never enable both publishing paths simultaneously.
7. Clear affected Elementor/page caches through their supported tools if necessary, and verify placement, mobile layout and actual signup-to-admin flow. No separate landing page is created.

To stop displaying the section, unpublish the dedicated Elementor snippet (or uncheck the plugin setting if using the alternative plugin). Retain the waitlist table when rolling back application code unless the owner explicitly requests removal of collected data. The migration's `down()` removes that table and must not be run on collected registrations as a routine rollback.
