#!/usr/bin/env bash
set -e

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ ! -f .env ]]; then
    echo "Filament Blueprint skipped (no license). Create .env, set FILAMENT_BLUEPRINT_EMAIL and FILAMENT_BLUEPRINT_LICENSE_KEY, then run this script to install."
    exit 0
fi

EMAIL="$(grep -E '^FILAMENT_BLUEPRINT_EMAIL=' .env 2>/dev/null | cut -d= -f2- | sed 's/^["'\'']//;s/["'\'']$//' || true)"
KEY="$(grep -E '^FILAMENT_BLUEPRINT_LICENSE_KEY=' .env 2>/dev/null | cut -d= -f2- | sed 's/^["'\'']//;s/["'\'']$//' || true)"

if [[ -z "$EMAIL" || -z "$KEY" ]]; then
    echo "Filament Blueprint skipped (no license). Set FILAMENT_BLUEPRINT_EMAIL and FILAMENT_BLUEPRINT_LICENSE_KEY in .env to install."
    exit 0
fi

echo "Installing Filament Blueprint..."
composer config repositories.filament composer https://packages.filamentphp.com/composer
composer config --auth http-basic.packages.filamentphp.com "$EMAIL" "$KEY"
composer require filament/blueprint --dev
echo "Running Boost installer. When prompted, select Filament Blueprint."
php artisan boost:install
