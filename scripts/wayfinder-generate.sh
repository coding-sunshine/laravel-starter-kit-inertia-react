#!/usr/bin/env bash
# Run wayfinder:generate in two steps so we always exit 0 (full run can exit 255 after routes).
set -e
cd "$(dirname "$0")/.."
php -d memory_limit=512M artisan wayfinder:generate --skip-routes "$@"
php -d memory_limit=512M artisan wayfinder:generate --skip-actions "$@"
