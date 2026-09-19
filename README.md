# EmpowerED Health — Admin panel & API

Laravel 11 backend for **EmpowerED Health**: a school / parent / child wellbeing product (mood tracking, quizzes, articles, video/podcasts, subscriptions).

This repository is the **admin panel** (Blade) and the **mobile API** (`/api`). Production host used by the app: `https://admin.empoweredhealth.asia`.

## What it includes

| Area | Notes |
| --- | --- |
| Admin UI | Schools, users, roles, articles (knowledge sessions), knowledge-base videos/podcasts, quizzes, mood tracker, payments, notifications, FAQs, static pages |
| Mobile API | Passport (`auth:api`): auth, children, knowledge sessions, video content, quizzes, mood, subscriptions/webhooks |
| Deep links | HTTPS `/d/article/{id}` and `/d/podcast/{id}`, plus Apple AASA and Android `assetlinks.json` (see `DEEP_LINKING.md`) |

Audience types on content: `parent`, `child`, `both` (all). Stack: PHP 8.2+, Laravel 11, MySQL, Passport, Stripe Cashier, S3 (optional).

## Requirements

- PHP **8.2** or **8.3** with extensions: `bcmath`, `ctype`, `curl`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `gd` (or `imagick`)
- **Composer** 2 — only needed to install or change dependencies. If `vendor/` is already present you can run the app without it.
- **MySQL** 8 (local or RDS)
- **FFmpeg** on the PATH if you upload/transcode video (`pbmedia/laravel-ffmpeg`)
- Node 18+ only if you run Vite (`npm run dev` / `npm run build`); the admin UI mostly uses Blade + `public/assets`

## Configure

```bash
cp .env.example .env
php artisan key:generate
```

Point `.env` at a **local** database. Do not use production RDS from a laptop checkout.

```env
APP_NAME="EmpowerED Health"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ec_healthcare
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=log
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
```

Admin login requires an email OTP in **every** environment, local included — see [Signing in locally](#signing-in-locally-otp) for how to read it.

### Optional env

| Variable | Purpose |
| --- | --- |
| `AWS_*` | S3 for images/video in non-local environments (`getImagePathUrl`) |
| `STRIPE_*` / Cashier | Subscriptions (set in `.env` if you test payments) |
| `DEEPLINK_PUBLIC_BASE_URL` | Canonical share URLs. Production: `https://admin.empoweredhealth.asia` |
| `DEEPLINK_APPLE_TEAM_ID` | Default `R8V8Y45CQZ` |
| `DEEPLINK_ANDROID_PACKAGE` | `asia.empoweredhealth` |
| `DEEPLINK_ANDROID_SHA256` | Comma-separated debug + Play App Signing fingerprints |
| `DEEPLINK_SCHEME` | `empowered` |

## Install & run (local)

Create the database once:

```sql
CREATE DATABASE ec_healthcare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then:

```bash
composer install
php artisan migrate
php artisan db:seed
php artisan passport:keys
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

Admin: [http://127.0.0.1:8000](http://127.0.0.1:8000)

| | |
| --- | --- |
| Email | `admin@empowered.local` |
| Password | `Admin@1234` |

Seeded by `AdminUserSeeder` (also creates admin menu rows and view/modify permissions). `php artisan db:seed` also runs `FeaturesContentSeeder` and `StaticContentSeeder`.

Change that password before any shared or production use.

### Signing in locally (OTP)

There is **no fixed or magic OTP**, and no local bypass. `___otp_code()` is `rand(1000, 9999)`, so the code is a
random four digits that rotates on every login attempt and every resend. Both historical shortcuts are commented out
and should stay that way — `'4444'` in [`LoginController::verifyOtp`](app/Http/Controllers/Admin/LoginController.php)
and `'111111'` in [`RegisterService::verifyOtp`](app/Services/RegisterService.php).

Submit the email and password first, then read the code that was generated:

```bash
php artisan tinker --execute="echo \App\Models\User::where('email','admin@empowered.local')->value('otp');"
```

Read it *after* submitting, not before — the previous value is already stale by then.

The same column holds the OTP for a mobile-API user part-way through registration:

```bash
php artisan tinker --execute="echo \App\Models\User::where('email','PARENT_EMAIL')->value('otp');"
```

With `MAIL_MAILER=log` the admin OTP is also written into `storage/logs/laravel.log`, but the template renders it
inside a styled block, so grepping for digits picks up noise. Prefer the database read above.

### Mail and queues locally

`MAIL_MAILER=log` means no mail leaves the machine — everything renders into the log:

```bash
tail -f storage/logs/laravel.log
```

The school parent import **dispatches** `SendStudentSignupMail` rather than sending inline, because sending N
credential emails inside one HTTP request times out a large import. With `QUEUE_CONNECTION=database` those jobs sit in
the `jobs` table until something drains them. In production the scheduler does it (`app/Console/Kernel.php`); locally
nothing does, so run a worker in a second terminal whenever you test an import:

```bash
php artisan queue:work
```

Without it the import still succeeds, but no credential email is ever rendered.

> **Twilio sends real SMS from your laptop.** `___sms_sender` calls the live Twilio API on every successful
> registration, and `.env` normally carries working credentials. The test suite is safe — `phpunit.xml` blanks
> `TWILIO_SID` — but **running the register API locally will send a genuine message** to whatever number you pass.
> Use an obviously fake number, or blank `TWILIO_SID` in `.env` while developing.

### Stopping the server

`php artisan serve` and `php artisan queue:work` both run in the foreground, so **Ctrl+C** in that terminal stops them.

Check what is actually running before killing anything:

```bash
pgrep -fl "artisan serve|artisan queue:work"
```

If one is orphaned — terminal closed, or started with `&` — match on the command, not the port:

```bash
pkill -f "artisan serve"
```

`artisan serve` spawns a child PHP built-in server that holds the socket, so killing only the port-holder
(`lsof -ti tcp:8000 | xargs kill`) leaves the parent alive and it can hand the port straight back. `pkill -f` takes
both. Confirm with the `pgrep` above; add `-9` only if something refuses to exit.

Nothing here installs a background service. The scheduled `queue:work` entry only fires when **cron** runs
`schedule:run`, which is a production concern, so there is no lingering process on a dev machine.

### Front-end assets (optional)

```bash
npm install
npm run dev      # or npm run build
```

## API

Base URL: `{APP_URL}/api` (local `http://127.0.0.1:8000/api`, production `https://admin.empoweredhealth.asia/api`).

Typical flow: `POST /api/login` → Bearer token on `Authorization` → protected routes (`auth:api`).

Deep link metadata (auth optional):

```text
GET /api/deeplink/resolve?type=article|podcast&id=123
```

Statuses: `ok`, `not_found`, `unpublished`, `forbidden_role`, `subscription_required`.

Public (no admin login):

- `/.well-known/apple-app-site-association`
- `/apple-app-site-association`
- `/.well-known/assetlinks.json`
- `/d/article/{id}` and `/d/podcast/{id}`

Do not restore a static AASA file under `public/.well-known/` with `"paths": ["*"]`; Laravel serves a scoped AASA from `routes/deeplink.php`.

## Project layout

```text
app/Http/Controllers/Admin/   Admin Blade controllers
app/Http/Controllers/Api/     Mobile API
app/Services/DeepLinkService.php
routes/web.php                Admin + public web
routes/api.php                Mobile API
routes/deeplink.php           AASA, assetlinks, /d/* fallback
resources/views/admin/        Admin UI
config/deeplink.php           Deep link env mapping
```

## Deploy on AWS EC2

This is a PHP/MySQL app. Run it on **EC2 + RDS (+ S3 for media)**. Netlify / Vercel cannot host it.

GitHub’s cloud runners **cannot** SSH to your instance (`dial tcp :22: i/o timeout`) unless security group 22 is open to the internet. Prefer: **first install on the box by SSH from your laptop**, then a **self-hosted Actions runner** on that same instance for later deploys.

### 1. AWS pieces

| Piece | Role |
| --- | --- |
| EC2 (Ubuntu 22.04/24.04) | nginx + PHP-FPM + the Laravel code |
| RDS MySQL 8 | Database (security group: allow **3306 from the EC2 SG only**) |
| Elastic IP + DNS | `admin.empoweredhealth.asia` → that IP |
| S3 | Images/video if `FILESYSTEM_DISK` / helpers use AWS |
| Security groups | Inbound **22** from *your* IP; **80/443** from `0.0.0.0/0`; no public 3306 |

SSH from your machine (not from GitHub):

```bash
ssh -i your-key.pem ubuntu@YOUR_PUBLIC_IP
```

### 2. Software on the instance (Ubuntu)

```bash
sudo apt-get update
sudo apt-get install -y nginx git unzip rsync ffmpeg \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring \
  php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl

curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Use `php8.2-*` if the AMI only has 8.2.

### 3. App directory and `.env`

```bash
sudo mkdir -p /var/www/ec-healthcare
sudo chown -R ubuntu:www-data /var/www/ec-healthcare
cd /var/www/ec-healthcare
git clone git@github.com:YOUR_ORG/AdminPanel.git .
# or: git clone https://github.com/YOUR_ORG/AdminPanel.git .
```

Create **production** `.env` on the server only (never commit it):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://admin.empoweredhealth.asia
DEEPLINK_PUBLIC_BASE_URL=https://admin.empoweredhealth.asia

DB_CONNECTION=mysql
DB_HOST=your-rds.amazonaws.com
DB_DATABASE=ec_healthcare_db
DB_USERNAME=...
DB_PASSWORD=...

FILESYSTEM_DISK=s3
MAIL_MAILER=smtp
QUEUE_CONNECTION=database
```

Then:

```bash
cd /var/www/ec-healthcare
composer install --no-dev --optimize-autoloader
php artisan key:generate          # once, if APP_KEY empty
php artisan migrate --force       # once / when you intend schema changes
php artisan passport:keys         # once, unless keys already exist
php artisan storage:link
sudo chown -R ubuntu:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. nginx (document root = `public/`)

Site config must send **everything** including `/.well-known/` and `/d/` to `index.php` (deep links). Do not auth-protect those paths.

```nginx
server {
    listen 80;
    server_name admin.empoweredhealth.asia;
    root /var/www/ec-healthcare/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    # Videos no longer travel through nginx: the browser PUTs them straight to
    # S3 (docs/DEPLOYMENT.md), so this only has to cover thumbnails and
    # form fields. Raising it is not how you allow bigger videos.
    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ^~ /.well-known/ {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        # Default fastcgi_read_timeout is 60s. Podcast upload + S3 + ffmpeg
        # often exceeds that after the browser already shows 100% progress.
        fastcgi_read_timeout 300s;
        fastcgi_send_timeout 300s;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/ec-healthcare /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo apt-get install -y certbot python3-certbot-nginx
sudo certbot --nginx -d admin.empoweredhealth.asia
```

PHP (php.ini / pool) must match the 64M nginx cap and the 300s FastCGI timeout, or the worker dies with browser `xhr.status === 0`:

```ini
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
```

Admin video uploads also need the S3 bucket CORS rule (`ExposeHeaders: ETag`) and the `assets/video/tmp/` lifecycle rule from [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) §1. Without them uploads silently fall back to posting through PHP and large files fail with 413 again.

FCM after a podcast save is queued (`NotifyVideoContentAudience`). On the admin host use a real queue (`QUEUE_CONNECTION=database` or `redis`, not `sync`) and keep `php artisan queue:work` (or supervisor) running. Details: [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) §4.

### 5. Later deploys

> The full release runbook — pre-flight, migrations, queue, smoke tests, rollback — is
> [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md). What follows is the short form.


**A — Manual (always works)**

```bash
cd /var/www/ec-healthcare
git fetch origin
git reset --hard origin/sprint1_dev   # or main
composer install --no-dev --optimize-autoloader
php artisan migrate --force           # only when you mean to
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl reload php8.3-fpm
```

**B — GitHub Actions on a self-hosted runner** (no inbound 22 from GitHub)

1. Repo → **Settings** → **Actions** → **Runners** → **New self-hosted runner** → Linux. Run `config.sh` / `svc.sh` on EC2 until the runner is **Idle**.
2. Environment **production** secret: `APP_PATH=/var/www/ec-healthcare`. Optional: `PHP_BIN`, `PHP_FPM_SERVICE=php8.3-fpm`.
3. Push to `sprint1_dev` / `main` or run **Deploy to EC2**. The job rsyncs the checkout after tarring the live tree (keeps `.env` and `storage/`).
4. Rollback: Actions → **Rollback EC2** → archive `latest`.

The runner user must write `APP_PATH`. Install `rsync`. Migrations are **off** unless you tick them on a manual workflow run. RDS snapshots are the DB rollback.

### 6. Smoke test after go-live

```bash
curl -sI https://admin.empoweredhealth.asia/.well-known/apple-app-site-association
# 200, Content-Type: application/json, no Location: /login
```

Open `https://admin.empoweredhealth.asia` and log in as an admin. Mobile API base: `https://admin.empoweredhealth.asia/api`.

## Tests

```bash
php artisan test
```

`phpunit.xml` blanks `TWILIO_SID` / `TWILIO_AUTH_TOKEN` / `TWILIO_NUMBER`. Without that the suite makes live Twilio
API calls on every registration test — slow, billable, and one real phone number away from sending an actual SMS from
a test run. Leave them blank.

Two gates worth knowing before you change API behaviour:

- `tests/Feature/Api/ResponseContractSnapshotTest.php` records the v1 response shape of every endpoint and fails if a
  key is removed, renamed or retyped. Additive keys pass by design. Regenerate only after reviewing the diff:
  `UPDATE_API_SNAPSHOTS=1 php vendor/bin/phpunit tests/Feature/Api/ResponseContractSnapshotTest.php`
- `tests/Feature/School/` covers the parent roster and child-seat caps, including the flag-off cases that pin
  unchanged behaviour for schools that have not opted in.

## License

Application code follows the project’s existing Laravel MIT skeleton unless otherwise specified.
