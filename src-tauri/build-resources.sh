#!/bin/bash
set -e

# Prepare a production copy of the Laravel project for bundling into the Tauri app.
# This script is called by tauri.conf.json's beforeBuildCommand.

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
RESOURCES_DIR="$SCRIPT_DIR/resources/laravel"

echo "[build-resources] Preparing Laravel project for bundling..."

# Clean previous bundle
rm -rf "$RESOURCES_DIR"
mkdir -p "$RESOURCES_DIR"

cd "$PROJECT_ROOT"

# Cache routes and views but NOT config — config:cache bakes in absolute paths
# from the build machine which won't exist inside the app bundle at runtime.
php artisan config:clear
php artisan route:cache
php artisan view:cache

# Build frontend assets
npm run build 2>/dev/null || echo "[build-resources] npm run build skipped (assets may already be built)"

# Copy only the directories FrankenPHP needs to serve the app.
# Use -RL for vendor/ to dereference symlinks (path repositories like circuit-breaker).
for dir in app bootstrap config database public resources routes storage packages; do
    if [ -d "$dir" ]; then
        cp -R "$dir" "$RESOURCES_DIR/$dir"
    fi
done
if [ -d "vendor" ]; then
    cp -RL "vendor" "$RESOURCES_DIR/vendor"
fi

# Copy root-level files needed by Laravel
for file in artisan composer.json .env .rr.yaml; do
    if [ -f "$file" ]; then
        cp "$file" "$RESOURCES_DIR/$file"
    fi
done

# Remove cached config if it was copied — it contains build-machine paths.
rm -f "$RESOURCES_DIR/bootstrap/cache/config.php"

# Ensure writable storage structure exists
mkdir -p "$RESOURCES_DIR/storage/app/public"
mkdir -p "$RESOURCES_DIR/storage/framework/cache/data"
mkdir -p "$RESOURCES_DIR/storage/framework/sessions"
mkdir -p "$RESOURCES_DIR/storage/framework/testing"
mkdir -p "$RESOURCES_DIR/storage/framework/views"
mkdir -p "$RESOURCES_DIR/storage/logs"

# Ensure SQLite database exists
if [ ! -f "$RESOURCES_DIR/database/database.sqlite" ]; then
    touch "$RESOURCES_DIR/database/database.sqlite"
fi

# Symlink storage into public (FrankenPHP serves from public/)
cd "$RESOURCES_DIR"
if [ ! -L "public/storage" ]; then
    ln -sf ../storage/app/public public/storage
fi

echo "[build-resources] Laravel project bundled to $RESOURCES_DIR"
du -sh "$RESOURCES_DIR" | awk '{print "[build-resources] Bundle size: " $1}'
