#!/usr/bin/env bash
# Run wayfinder:generate in two steps. Always exit 0 so Vite plugin does not fail (e.g. when project path contains spaces).
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT" || exit 0
php -d memory_limit=512M artisan wayfinder:generate --skip-routes "$@" 2>/dev/null || true
php -d memory_limit=512M artisan wayfinder:generate --skip-actions "$@" 2>/dev/null || true
exit 0
