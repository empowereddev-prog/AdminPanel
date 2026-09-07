#!/usr/bin/env bash
# Backup / deploy / rollback the Laravel app on EC2.
# Intended to run ON the server (GitHub Actions copies it, then SSH-executes it).
#
# Required env: APP_PATH
# Optional: BACKUP_DIR, BACKUP_KEEP, GIT_SHA, GIT_BRANCH, RUN_MIGRATIONS,
#           PHP_BIN, PHP_FPM_SERVICE, RELEASE_SRC (workspace checkout on a self-hosted runner)

set -euo pipefail

usage() {
  echo "Usage: $0 backup|deploy|deploy-sync|rollback|list [archive-name|latest]"
  exit 1
}

APP_PATH="${APP_PATH:?Set APP_PATH to the live Laravel root, e.g. /var/www/ec-healthcare}"
BACKUP_DIR="${BACKUP_DIR:-$(dirname "$APP_PATH")/backups/$(basename "$APP_PATH")}"
BACKUP_KEEP="${BACKUP_KEEP:-5}"
GIT_SHA="${GIT_SHA:-}"
GIT_BRANCH="${GIT_BRANCH:-}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-false}"
SKIP_BACKUP="${SKIP_BACKUP:-false}"
PHP_BIN="${PHP_BIN:-php}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-}"
RELEASE_SRC="${RELEASE_SRC:-}"

command="${1:-}"
archive_arg="${2:-latest}"

log() { echo "[$(date -u +%Y-%m-%dT%H:%M:%SZ)] $*"; }

current_sha() {
  if git -C "$APP_PATH" rev-parse --short HEAD >/dev/null 2>&1; then
    git -C "$APP_PATH" rev-parse --short HEAD
  else
    echo "unknown"
  fi
}

reload_fpm() {
  if [[ -n "$PHP_FPM_SERVICE" ]]; then
    sudo systemctl reload "$PHP_FPM_SERVICE" || true
    return
  fi
  for svc in php8.3-fpm php8.2-fpm php-fpm; do
    if systemctl list-unit-files "$svc.service" >/dev/null 2>&1; then
      sudo systemctl reload "$svc" || true
      return
    fi
  done
  log "PHP-FPM service not found; skip reload"
}

# The locked dependencies carry known security advisories (see docs/DEPLOYMENT.md).
# Composer 2.9+ refuses to install those unless blocking is disabled, but the flag
# does not exist on older 2.x, where passing it is a hard error. Detect it.
composer_blocking_flag() {
  if composer install --help 2>/dev/null | grep -q -- '--no-security-blocking'; then
    echo "--no-security-blocking"
  fi
}

laravel_optimize() {
  cd "$APP_PATH"
  "$PHP_BIN" artisan config:clear || true
  "$PHP_BIN" artisan cache:clear || true
  "$PHP_BIN" artisan view:clear || true
  "$PHP_BIN" artisan config:cache
  "$PHP_BIN" artisan route:cache
  "$PHP_BIN" artisan view:cache
}

prune_backups() {
  mkdir -p "$BACKUP_DIR"
  local extras
  extras="$(ls -1t "$BACKUP_DIR"/pre-deploy-*.tar.gz 2>/dev/null | tail -n +"$((BACKUP_KEEP + 1))" || true)"
  if [[ -n "$extras" ]]; then
    log "Pruning old backups (keep $BACKUP_KEEP)"
    echo "$extras" | xargs rm -f
    echo "$extras" | sed 's/\.tar\.gz$/.sha/' | xargs rm -f 2>/dev/null || true
    echo "$extras" | sed 's/\.tar\.gz$/.txt/' | xargs rm -f 2>/dev/null || true
  fi
}

latest_archive() {
  ls -1t "$BACKUP_DIR"/pre-deploy-*.tar.gz 2>/dev/null | head -n 1
}

do_backup() {
  mkdir -p "$BACKUP_DIR"
  if [[ ! -d "$APP_PATH" ]]; then
    log "No live app at $APP_PATH yet; skip backup"
    return 0
  fi

  local stamp sha name
  stamp="$(date -u +%Y%m%d-%H%M%S)"
  sha="$(current_sha)"
  name="pre-deploy-${stamp}-${sha}"

  log "Backing up live deploy to $BACKUP_DIR/${name}.tar.gz"
  # Snapshot code + vendor as deployed. Keep .env, storage uploads, and logs on the server.
  tar -C "$APP_PATH" -czf "$BACKUP_DIR/${name}.tar.gz" \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='storage' \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='bootstrap/cache/*.php' \
    .

  printf '%s\n' "$sha" > "$BACKUP_DIR/${name}.sha"
  cat > "$BACKUP_DIR/${name}.txt" <<EOF
created_at_utc=$(date -u +%Y-%m-%dT%H:%M:%SZ)
app_path=$APP_PATH
git_sha=$sha
replaced_by_sha=${GIT_SHA:-}
branch=${GIT_BRANCH:-}
archive=${name}.tar.gz
EOF
  ln -sfn "${name}.tar.gz" "$BACKUP_DIR/latest.tar.gz"
  ln -sfn "${name}.txt" "$BACKUP_DIR/latest.txt"
  prune_backups
  log "Backup complete: ${name}.tar.gz"
}

do_list() {
  mkdir -p "$BACKUP_DIR"
  log "Backups in $BACKUP_DIR (newest first):"
  ls -lh "$BACKUP_DIR"/pre-deploy-*.tar.gz 2>/dev/null || echo "(none)"
}

do_deploy() {
  if [[ "$SKIP_BACKUP" == "true" ]]; then
    log "SKIP_BACKUP=true; not snapshotting the live app"
  else
    do_backup
  fi

  cd "$APP_PATH"
  if git rev-parse --git-dir >/dev/null 2>&1; then
    log "Updating git at $APP_PATH"
    git fetch origin
    if [[ -n "$GIT_SHA" ]]; then
      git reset --hard "$GIT_SHA"
    elif [[ -n "$GIT_BRANCH" ]]; then
      git checkout "$GIT_BRANCH"
      git reset --hard "origin/$GIT_BRANCH"
    else
      git pull --ff-only
    fi
  else
    log "ERROR: $APP_PATH is not a git checkout; abort after backup"
    exit 1
  fi

  finish_deploy
}

finish_deploy() {
  cd "$APP_PATH"
  if [[ -f composer.json ]]; then
    log "composer install"
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader $(composer_blocking_flag)
  fi

  if [[ "$RUN_MIGRATIONS" == "true" ]]; then
    log "Running migrations"
    "$PHP_BIN" artisan migrate --force
  fi

  laravel_optimize
  reload_fpm
  log "Deploy finished (sha=$(current_sha))"
}

do_deploy_sync() {
  RELEASE_SRC="${RELEASE_SRC:?Set RELEASE_SRC to the GitHub Actions workspace}"
  if [[ ! -f "$RELEASE_SRC/artisan" && ! -f "$RELEASE_SRC/composer.json" ]]; then
    log "ERROR: RELEASE_SRC does not look like the Laravel repo: $RELEASE_SRC"
    exit 1
  fi

  if [[ "$SKIP_BACKUP" == "true" ]]; then
    log "SKIP_BACKUP=true; not snapshotting the live app"
  else
    do_backup
  fi

  mkdir -p "$APP_PATH"
  log "Syncing $RELEASE_SRC -> $APP_PATH"
  rsync -a --delete \
    --exclude '.env' \
    --exclude '.env.*' \
    --exclude '.git/' \
    --exclude 'storage/' \
    --exclude 'vendor/' \
    --exclude 'node_modules/' \
    --exclude 'public/uploads/' \
    --exclude 'public/assets/' \
    --exclude 'public/storage/' \
    --exclude 'public/phpdb/' \
    --exclude 'bootstrap/cache/*.php' \
    "$RELEASE_SRC"/ "$APP_PATH"/

  finish_deploy
}

do_rollback() {
  local archive=""
  mkdir -p "$BACKUP_DIR"

  if [[ "$archive_arg" == "latest" ]]; then
    archive="$(latest_archive || true)"
  elif [[ -f "$archive_arg" ]]; then
    archive="$archive_arg"
  elif [[ -f "$BACKUP_DIR/$archive_arg" ]]; then
    archive="$BACKUP_DIR/$archive_arg"
  elif [[ -f "$BACKUP_DIR/${archive_arg}.tar.gz" ]]; then
    archive="$BACKUP_DIR/${archive_arg}.tar.gz"
  fi

  if [[ -z "$archive" || ! -f "$archive" ]]; then
    log "No backup archive found. Run: $0 list"
    exit 1
  fi

  local env_copy
  env_copy="$(mktemp)"
  if [[ -f "$APP_PATH/.env" ]]; then
    cp -a "$APP_PATH/.env" "$env_copy"
  fi

  log "Rolling back from $archive"
  mkdir -p "$APP_PATH"
  tar -C "$APP_PATH" -xzf "$archive"
  if [[ -s "$env_copy" ]]; then
    cp -a "$env_copy" "$APP_PATH/.env"
  fi
  rm -f "$env_copy"

  cd "$APP_PATH"
  if [[ -f composer.json && -d vendor ]]; then
    composer dump-autoload --optimize --no-dev --no-interaction || true
  elif [[ -f composer.json ]]; then
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader $(composer_blocking_flag)
  fi

  laravel_optimize
  reload_fpm
  log "Rollback finished from $(basename "$archive")"
}

case "$command" in
  backup) do_backup ;;
  deploy) do_deploy ;;
  deploy-sync) do_deploy_sync ;;
  rollback) do_rollback ;;
  list) do_list ;;
  *) usage ;;
esac
