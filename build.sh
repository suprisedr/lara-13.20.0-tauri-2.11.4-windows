#!/bin/bash
set -e

echo "Building Chainbook desktop application..."

# Load .env.tauri
if [ -f .env.tauri ]; then
    export $(cat .env.tauri | grep -v '^#' | xargs)
fi

# Ensure data directories exist
mkdir -p data/pgdata data/meilisearch storage/app/public storage/framework/{cache,sessions,testing,views} storage/logs

# Install PHP dependencies
echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies and build frontend assets
echo "Building frontend assets..."
npm ci
npm run build

# Ensure SQLite database exists
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Optimize Laravel
echo "Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Download platform-specific sidecar binaries
echo "Preparing sidecar binaries..."
ARCH=$(uname -m)
OS=$(uname -s | tr '[:upper:]' '[:lower:]')

BINARIES_DIR="src-tauri/binaries"
mkdir -p "$BINARIES_DIR"

# Determine Tauri target triple
case "$OS" in
    darwin)
        case "$ARCH" in
            arm64) TARGET_TRIPLE="aarch64-apple-darwin" ;;
            x86_64) TARGET_TRIPLE="x86_64-apple-darwin" ;;
        esac
        ;;
    linux)
        case "$ARCH" in
            aarch64) TARGET_TRIPLE="aarch64-unknown-linux-gnu" ;;
            x86_64) TARGET_TRIPLE="x86_64-unknown-linux-gnu" ;;
        esac
        ;;
esac

echo "Target triple: $TARGET_TRIPLE"

# Note: FrankenPHP binary is built by 'php artisan tauri:build'
# The following binaries need to be placed manually or downloaded:
#   - binaries/meilisearch-$TARGET_TRIPLE
#   - binaries/temporal-$TARGET_TRIPLE
#   - binaries/rr-$TARGET_TRIPLE
#   - binaries/postgres-$TARGET_TRIPLE
#
# Download Meilisearch: https://github.com/meilisearch/meilisearch/releases
# Download Temporal: https://github.com/temporalio/cli/releases
# Download RoadRunner: https://github.com/roadrunner-server/roadrunner/releases
# PostgreSQL: Use embedded PostgreSQL or download from https://github.com/zonkyio/embedded-postgres-binaries

for bin in meilisearch temporal rr postgres; do
    if [ ! -f "$BINARIES_DIR/$bin-$TARGET_TRIPLE" ]; then
        echo "WARNING: $BINARIES_DIR/$bin-$TARGET_TRIPLE not found. Please download it manually."
    else
        chmod +x "$BINARIES_DIR/$bin-$TARGET_TRIPLE"
        echo "Found $bin binary"
    fi
done

# Build FrankenPHP via tauri-php
echo "Building FrankenPHP binary..."
php artisan tauri:build 2>/dev/null || echo "FrankenPHP build requires Docker. Run 'php artisan tauri:build' separately if needed."

# Build Tauri application
echo "Building Tauri application..."
npm run tauri build

echo "Build completed!"
echo "Artifacts location: src-tauri/target/release/bundle/"
