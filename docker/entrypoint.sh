#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env from .env.example"
fi

sync_env_var() {
    key="$1"
    value="$2"

    if grep -q "^${key}=" .env 2>/dev/null; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        printf '%s=%s\n' "$key" "$value" >> .env
    fi
}

# When running under Docker Compose, align .env with service hostnames so
# php artisan serve and Horizon use postgres/redis containers, not localhost.
if [ "${DB_HOST:-}" = "postgres" ]; then
    sync_env_var APP_ENV "${APP_ENV:-local}"
    sync_env_var APP_URL "${APP_URL:-http://localhost:8080}"
    sync_env_var DB_CONNECTION pgsql
    sync_env_var DB_HOST postgres
    sync_env_var DB_PORT "${DB_PORT:-5432}"
    sync_env_var DB_DATABASE "${DB_DATABASE:-inventory}"
    sync_env_var DB_USERNAME "${DB_USERNAME:-inventory}"
    sync_env_var DB_PASSWORD "${DB_PASSWORD:-secret}"
    sync_env_var REDIS_HOST "${REDIS_HOST:-redis}"
    sync_env_var REDIS_PORT "${REDIS_PORT:-6379}"
    sync_env_var CACHE_STORE redis
    sync_env_var QUEUE_CONNECTION redis
    sync_env_var SESSION_DRIVER redis
fi

if [ ! -f vendor/autoload.php ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

if ! grep -qE '^APP_KEY=base64:.+' .env 2>/dev/null; then
    php artisan key:generate --force --no-interaction
fi

echo "Waiting for PostgreSQL..."
until php -r "new PDO('pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 1
done

exec "$@"
