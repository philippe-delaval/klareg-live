#!/usr/bin/env bash
#
# Entrypoint shared by all Klareg Live containers (web, reverb, eventsub, irc).
# Waits for the database, then runs migrations ONCE per stack startup
# (only the web role triggers them; workers just wait until the DB is ready).
set -euo pipefail

cd /app

wait_for_db() {
    local host="${DB_HOST:-db}"
    local port="${DB_PORT:-5432}"
    echo "[entrypoint] waiting for ${host}:${port} ..."
    for i in $(seq 1 60); do
        if php -r "exit(@fsockopen('${host}', ${port}) ? 0 : 1);" 2>/dev/null; then
            echo "[entrypoint] ${host}:${port} is reachable"
            return 0
        fi
        sleep 1
    done
    echo "[entrypoint] giving up waiting for ${host}:${port}" >&2
    return 1
}

is_web_role() {
    case "${1:-}" in
        supervisord|/usr/bin/supervisord) return 0 ;;
        *) return 1 ;;
    esac
}

wait_for_db

if is_web_role "$@"; then
    echo "[entrypoint] running database migrations"
    php artisan migrate --force || true
    # Don't cache config() — some env() reads happen outside of config/ files.
    php artisan route:cache    || true
    php artisan view:cache     || true
    php artisan event:cache    || true
fi

exec "$@"
