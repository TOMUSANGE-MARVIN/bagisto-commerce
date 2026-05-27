#!/bin/bash
set -Eeuo pipefail

APP_DIR="/var/www/bagisto"
cd "$APP_DIR"

log() { echo "[bagisto-runtime-install] $(date '+%Y-%m-%d %H:%M:%S') $*"; }

DB_HOST="${DB_HOST:-mariadb}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-bagisto}"
DB_USERNAME="${DB_USERNAME:-bagisto}"
DB_PASSWORD="${DB_PASSWORD:-bagisto}"

ADMIN_NAME="${ADMIN_NAME:-Administrator}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-bagisto123}"

log "Waiting for external DB at ${DB_HOST}:${DB_PORT}..."
for i in $(seq 1 120); do
    if php -r "
        try {
            new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}',
                '${DB_USERNAME}', '${DB_PASSWORD}');
        } catch (Throwable \$e) { exit(1); }
    " 2>/dev/null; then
        log "External DB is reachable."
        break
    fi
    if [ "$i" -eq 120 ]; then
        log "ERROR: Cannot reach external DB."
        exit 1
    fi
    sleep 1
done

# ==========================================================================
# Ensure storage paths exist
# ==========================================================================
log "Ensuring storage paths exist..."
mkdir -p \
    storage/framework/views \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# ==========================================================================
# APP_KEY
# ==========================================================================
APP_KEY_CURRENT="$(grep '^APP_KEY=' .env | tail -n1 | grep -Eo 'base64:[A-Za-z0-9+\/=]+' || true)"

if [ -z "${APP_KEY_CURRENT:-}" ]; then
    log "Generating APP_KEY..."
    php artisan key:generate --force --no-interaction || true
fi

# ==========================================================================
# First-time installation check
# ==========================================================================
log "Checking if Bagisto is already installed..."

users_count=$(php -r "
    try {
        \$dsn = 'mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}';
        \$pdo = new PDO(\$dsn, '${DB_USERNAME}', '${DB_PASSWORD}');
        \$exists = (int)\$pdo->query(\"
            SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema='${DB_DATABASE}' AND table_name='admins'
        \")->fetchColumn();
        echo \$exists ? (int)\$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() : 0;
    } catch (Throwable \$e) { echo 0; }
" 2>/dev/null || echo 0)

if [ "${users_count:-0}" -gt 0 ]; then
    log "Bagisto already installed (${users_count} admin user(s)). Running migrations only..."
    php artisan migrate --force --no-interaction || true
    log "Migrations complete."
else
    log "Running first-time Bagisto installation (this may take a few minutes)..."
    timeout 1200 php artisan bagisto:install \
        --skip-env-check \
        --skip-github-star \
        --no-interaction

    touch storage/installed
    log "First-time installation complete."
fi

log "Ensuring storage symlink exists..."
php artisan storage:link --force --no-interaction 2>/dev/null || true

# ==========================================================================
# Stop temporary internal MariaDB (supervisord will start it properly)
# ==========================================================================
log "Runtime install checks complete."
