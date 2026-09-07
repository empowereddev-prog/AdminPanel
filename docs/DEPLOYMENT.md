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

## Known gap: `composer.lock` is not committed

`.gitignore` currently lists `composer.lock`, and the file is untracked. Every CI run
and every deploy therefore resolves dependency versions afresh, so the runner and the
server can install different code from the same commit — and an upstream patch release
can break a deploy that touches no application code.

Fix it once:

```bash
sed -i '' '/^composer\.lock$/d' .gitignore
git add -f composer.lock .gitignore
git commit -m "Track composer.lock for reproducible builds"
```

(`.gitignore` is also currently self-ignored and absent from the remote, which is why
the command above force-adds it.) After this, switch the deploy's install step to
`composer install` against the lock file — it already does — and builds become
byte-identical across CI and the server.
