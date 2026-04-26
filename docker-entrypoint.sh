#!/usr/bin/env bash
set -e

# Cache framework configs/routes (idempotent — runs each container start).
# We don't run migrations here since the schema lives in Supabase Postgres
# and was loaded out-of-band via the migration scripts in _migration/.
php artisan config:cache  || true
php artisan route:cache   || true
php artisan view:cache    || true

# Generate Passport encryption keys if they don't already exist on disk.
# Railway's filesystem is ephemeral so these get regenerated on each new
# container — that's OK for a single-instance deploy. For multi-replica,
# mount a persistent volume to storage/ and remove this block.
if [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --no-interaction || true
fi

# Storage symlink so /storage/* serves the storage/app/public/ files.
# (Most uploads now go to Supabase Storage, but legacy reads may still hit this.)
if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

# Hand off to Apache (PID 1)
exec apache2-foreground
