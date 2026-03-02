#!/usr/bin/env bash
# Run projects/lots import with enough memory and options to avoid hanging.
# Usage: ./scripts/run-import-projects-lots.sh [--fresh]
set -e
cd "$(dirname "$0")/.."
OPTS="--chunk=200 --skip-events"
[[ "${1:-}" == "--fresh" ]] && OPTS="$OPTS --fresh"
php -d memory_limit=512M artisan fusion:import-projects-lots $OPTS
