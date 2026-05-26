#!/bin/bash
set -Eeuo pipefail

APP_DIR="/var/www/bagisto"
cd "$APP_DIR"

log() { echo "[bagisto-runtime-install] $(date '+%Y-%m-%d %H:%M:%S') $*"; }

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3308}"
DB_DATABASE="${DB_DATABASE:-bagisto}"
DB_USERNAME="${DB_USERNAME:-bagisto}"
DB_PASSWORD="${DB_PASSWORD:-bagisto}"

ADMIN_NAME="${ADMIN_NAME:-Administrator}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-bagisto123}"

use_internal_db() { [[ "$DB_HOST" == "127.0.0.1" || "$DB_HOST" == "localhost" ]]; }

# ==========================================================================
# Internal MariaDB: initialise data dir + start temporarily for install
# ==========================================================================
if use_internal_db; then
    log "Preparing internal MariaDB..."
    mkdir -p /run/mysqld /var/lib/mysql
    chown -R mysql:mysql /run/mysqld /var/lib/mysql

    if [ ! -d /var/lib/mysql/mysql ]; then
        log "Initialising MariaDB data directory..."
        mariadb-install-db --user=mysql --datadir=/var/lib/mysql \
            --skip-test-db --auth-root-authentication-method=normal \
            > /dev/null 2>&1
    fi

    log "Starting temporary MariaDB for installation..."
    mysqld --user=mysql --datadir=/var/lib/mysql \
        --pid-file=/run/mysqld/mysqld.pid \
        --socket=/run/mysqld/mysqld.sock \
        --port=3308 &
    MYSQL_PID=$!

    log "Waiting for MariaDB to be ready..."
    for i in $(seq 1 120); do
        if mysqladmin --socket=/run/mysqld/mysqld.sock --silent ping 2>/dev/null; then
            log "Internal MariaDB is ready."
            break
        fi
        if [ "$i" -eq 120 ]; then
            log "ERROR: MariaDB did not become ready."
            exit 1
        fi
        sleep 1
    done

    log "Ensuring database and user exist..."
    mysql --socket=/run/mysqld/mysqld.sock -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'127.0.0.1'
    IDENTIFIED BY '${DB_PASSWORD}';
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'localhost'
    IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'127.0.0.1';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'localhost';
FLUSH PRIVILEGES;
SQL

else
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
fi

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

SOCKET_OPT=""
if use_internal_db; then
    SOCKET_OPT="unix_socket=/run/mysqld/mysqld.sock;"
fi

users_count=$(php -r "
    try {
        \$dsn = 'mysql:${SOCKET_OPT}host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}';
        \$pdo = new PDO(\$dsn, '${DB_USERNAME}', '${DB_PASSWORD}');
        \$exists = (int)\$pdo->query(\"
            SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema='${DB_DATABASE}' AND table_name='admins'
        \")->fetchColumn();
        echo \$exists ? (int)\$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() : 0;
    } catch (Throwable \$e) { echo 0; }
" 2>/dev/null || echo 0)

if [ "${users_count:-0}" -gt 0 ]; then
    log "Bagisto already installed (${users_count} admin user(s)). Skipping install."
else
    log "Running first-time Bagisto installation (this may take a few minutes)..."
    timeout 1200 php artisan bagisto:install \
        --skip-env-check \
        --skip-github-star \
        --no-interaction

    touch storage/installed
    log "First-time installation complete."
fi

# ==========================================================================
# Stop temporary internal MariaDB (supervisord will start it properly)
# ==========================================================================
if use_internal_db; then
    log "Stopping temporary MariaDB..."
    mysqladmin --socket=/run/mysqld/mysqld.sock -u root shutdown 2>/dev/null || true
    wait "${MYSQL_PID:-0}" 2>/dev/null || true
    chown -R mysql:mysql /var/lib/mysql
fi

log "Runtime install checks complete."
