# Deployment (EC2, dev)

## How it works

```
push to sprint1_dev
      │
      ▼
  ci.yml  ──── ubuntu-latest: composer install, php -l, php artisan test
      │        (fails ⇒ nothing is deployed)
      ▼
 deploy job ── self-hosted runner ON the EC2 box
      │
      ├── snapshot live APP_PATH → BACKUP_DIR/pre-deploy-<ts>-<sha>.tar.gz
      ├── rsync --delete  runner workspace → APP_PATH
      │      (keeps .env, storage/, vendor/, public/uploads, public/assets)
      ├── composer install --no-dev --optimize-autoloader
      ├── (optional) php artisan migrate --force
      ├── config/route/view cache + php-fpm reload
      └── health check → auto-rollback if it never returns 200
```

The release is **rsynced from the runner's checkout**, not `git pull`ed on the server.
So `APP_PATH` does not need to be a git checkout of any repository — that is what
lets this repo take over the deploy from the old one without rebuilding the box.

Workflows:

| File | Trigger | Purpose |
|---|---|---|
| `.github/workflows/ci.yml` | PR + push to `sprint1_dev`/`main`, and called by deploy | Tests |
| `.github/workflows/deploy-ec2.yml` | push to `sprint1_dev`, manual | Release |
| `.github/workflows/rollback-ec2.yml` | manual only | Restore a snapshot |

`main` currently runs CI only — it does not deploy anywhere.

---

## One-time server setup

### 1. Register the self-hosted runner against **this** repo

In GitHub: **Settings → Actions → Runners → New self-hosted runner → Linux x64**.
That page shows a one-time token; use the commands it gives you, which look like:

```bash
sudo mkdir -p /opt/actions-runner && sudo chown "$USER" /opt/actions-runner
cd /opt/actions-runner
curl -o actions-runner-linux-x64.tar.gz -L <URL FROM THAT PAGE>
tar xzf actions-runner-linux-x64.tar.gz
./config.sh --url https://github.com/empowereddev-prog/AdminPanel --token <TOKEN FROM THAT PAGE> --labels self-hosted,linux --unattended
sudo ./svc.sh install
sudo ./svc.sh start
```

The labels must include `self-hosted` and `linux` — the deploy job selects on both.
Confirm it shows **Idle** on the Runners page before going further.

> If a runner is already registered on this box against the **old** repo, leave it
> running during cutover. Two runner services can coexist in separate directories;
> remove the old one with `sudo ./svc.sh stop && ./config.sh remove` once you're happy.

### 2. Let the runner write the app and reload PHP-FPM

The runner service user (whatever `whoami` printed above, often `ubuntu`) must be able
to write `APP_PATH`, and the web server user must still be able to read it. **This is the
most common cause of a green deploy that serves a 500.**

```bash
APP_PATH=/var/www/ec-healthcare        # adjust
RUNNER_USER=ubuntu                     # adjust

sudo usermod -aG www-data "$RUNNER_USER"
sudo chown -R "$RUNNER_USER":www-data "$APP_PATH"
sudo find "$APP_PATH" -type d -exec chmod 2775 {} +   # setgid: new files stay www-data
sudo find "$APP_PATH" -type f -exec chmod 0664 {} +
sudo chmod -R 2775 "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"
```

The script calls `sudo systemctl reload <php-fpm>`, so grant exactly that, password-free:

```bash
sudo tee /etc/sudoers.d/gh-runner-deploy >/dev/null <<'EOF'
ubuntu ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.3-fpm, /usr/bin/systemctl list-unit-files
EOF
sudo chmod 0440 /etc/sudoers.d/gh-runner-deploy
sudo visudo -c
```

Also make sure `rsync`, `git`, `composer`, `tar` and `curl` are installed on the box.

### 3. Create the `dev` GitHub Environment and its secrets

**Settings → Environments → New environment → `dev`**, then add:

| Secret | Example | Required |
|---|---|---|
| `APP_PATH` | `/var/www/ec-healthcare` | yes |
| `BACKUP_DIR` | `/var/www/backups/ec-healthcare` | no (defaults next to `APP_PATH`) |
| `BACKUP_KEEP` | `5` | no |
| `PHP_BIN` | `/usr/bin/php8.3` | no (defaults to `php`) |
| `PHP_FPM_SERVICE` | `php8.3-fpm` | recommended |

And under **Settings → Secrets and variables → Actions → Variables**:

| Variable | Example | Effect |
|---|---|---|
| `HEALTHCHECK_URL` | `https://admin.empoweredhealth.asia/login` | Enables the smoke test and auto-rollback. Leave unset to skip both. |

### 4. Cut over from the old repo

1. Confirm `APP_PATH/.env` exists on the box and is correct — rsync never touches it,
   and it is not in this repo.
2. Disable the old repo's deploy workflow (its **Actions → workflow → ⋯ → Disable**),
   or delete its runner. Two repos deploying to one `APP_PATH` will fight.
3. Trigger the first release manually: **Actions → Deploy to EC2 (dev) → Run workflow**,
   leaving `run_migrations` off. Watch the log for the `Backing up live deploy` line —
   that snapshot is your escape hatch.
4. Verify the site, then let pushes to `sprint1_dev` drive it from there.

---

## Day-to-day

**Normal release** — merge to `sprint1_dev`. CI must pass before the deploy job starts.

**Release with migrations** — Actions → *Deploy to EC2 (dev)* → Run workflow →
tick `run_migrations`. Migrations are never run automatically on push, on purpose.

**Rollback** — Actions → *Rollback EC2* → Run workflow → `latest`, or paste a specific
`pre-deploy-<timestamp>-<sha>.tar.gz` filename. The list of available snapshots is
printed at the end of every deploy log. Rollback preserves the live `.env`.

Note that rollback restores **code**, not the database. If a release ran migrations,
roll the schema back yourself before restoring the snapshot.

---

## Dependency locking

`composer.lock` is now committed, and `.gitignore` no longer excludes it (or itself —
it was previously self-ignored and absent from the remote entirely).

This is load-bearing, not housekeeping. Composer 2.9 refuses to *resolve* packages that
carry security advisories, so with no lock file every CI run and every deploy tried a
full re-resolve and failed outright:

```
Root composer.json requires laravel/framework ^11.9, found laravel/framework[v11.9.0, ..., v11.56.1]
but these were not loaded, because they are affected by security advisories
```

`composer install` against a lock file performs no resolution, so it installs the pinned
versions and that check never fires. CI and the server now build byte-identically from
the same 128 runtime packages. Both install commands pass `--no-audit` so a future
advisory cannot spontaneously break a deploy of unchanged code; auditing happens
explicitly in CI instead.

**Regenerating the lock.** `composer update` will hit the same advisory wall. That is the
tool working correctly — resolve the advisories rather than switching the check off
(`policy.advisories.block: false` would silence it and quietly reintroduce the
non-determinism this section exists to prevent).

### Outstanding advisories

Committing the lock unblocked the pipeline; it did not make the dependencies safe. The
locked versions have known advisories and this is what the app is already running in
production today:

| Package | Locked | Status |
|---|---|---|
| `laravel/framework` | v11.45.1 | 7 advisories; every `^11.x` release is flagged, so this needs a Laravel 12 upgrade |
| `dompdf/dompdf` | v2.0.8 | 6 advisories; fixed in dompdf 3.x, via `barryvdh/laravel-dompdf` ^3.0 |
| `illuminate/mail` | v11.45.1 | PKSA-zwc5-qtrz-zm1n, same v11 constraint |

The `Audit dependencies` step in CI prints the current list on every run. It is
`continue-on-error: true` today because it would otherwise fail every build — remove
that line once the table above is clear, so regressions become a hard gate.

Plan the upgrade as its own piece of work; `laravel/cashier` and
`yajra/laravel-datatables` both constrain the framework version and will need bumping
in the same change.
