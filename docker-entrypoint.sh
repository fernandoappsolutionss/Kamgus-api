#!/usr/bin/env bash
set -e

# Cache framework configs/routes/views (idempotent — runs each container start).
# Schema lives in Supabase Postgres and was loaded out-of-band; no migrations here.
php artisan config:cache  || true
php artisan route:cache   || true
php artisan view:cache    || true

# Generate Passport encryption keys if they don't already exist.
# Railway's filesystem is ephemeral, so these regenerate on each new container.
# For multi-replica deploys, mount a persistent volume on storage/.
if [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --no-interaction || true
fi

# Storage symlink: public/storage → storage/app/public.
if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

# Hand off to PHP's built-in server.
# server.php is Laravel's CLI router (handles missing-file → public/index.php fallback,
# the equivalent of Apache mod_rewrite for the dev server).
echo "Starting Kamgus API on 0.0.0.0:${PORT:-8080}..."
exec php -d variables_order=EGPCS \
         -d memory_limit=512M \
         -S 0.0.0.0:"${PORT:-8080}" \
         -t public \
         server.php
