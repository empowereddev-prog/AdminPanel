# Empowered Health — Admin panel & API

Laravel 11 backend for **Empowered Health**: a school / parent / child wellbeing product (mood tracking, quizzes, articles, video/podcasts, subscriptions).

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
- **Composer** 2
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
APP_NAME="Empowered Health"
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

When `APP_ENV=local`, admin login **skips email OTP** and signs you in directly.

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

### 5. Later deploys (two options)

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

## License

Application code follows the project’s existing Laravel MIT skeleton unless otherwise specified.
