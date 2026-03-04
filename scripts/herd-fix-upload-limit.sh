#!/usr/bin/env bash
# Creates a site-specific Nginx config in Herd (if missing) and adds client_max_body_size 100M
# so AI Assistant uploads > 1MB don't get HTTP 413. Run from project root.
# See docs/herd-upload-limit.md.

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

# Domain for this app (default from APP_URL; override with HERD_SITE env)
DOMAIN="${HERD_SITE:-laravel-starter-kit-inertia-react.test}"
PHP_VERSION="${HERD_PHP_VERSION:-8.3}"
NGINX_DIR="$HOME/Library/Application Support/Herd/config/valet/Nginx"
NGINX_FILE="$NGINX_DIR/$DOMAIN"
DIRECTIVE="client_max_body_size 100M"

if [[ ! -d "$NGINX_DIR" ]]; then
  echo "Herd Nginx config directory not found: $NGINX_DIR"
  echo "Is Laravel Herd installed and have you linked this project (e.g. herd link)?"
  exit 1
fi

echo "Site: $DOMAIN | PHP: $PHP_VERSION"
echo "Nginx config: $NGINX_FILE"

# Create site-specific config if missing (herd isolate creates it)
if [[ ! -f "$NGINX_FILE" ]]; then
  echo "Creating site Nginx config with: herd isolate $PHP_VERSION"
  herd isolate "$PHP_VERSION" || true
  if [[ ! -f "$NGINX_FILE" ]]; then
    echo "Config still missing. Create it from Herd (e.g. isolate PHP or secure site), then run this script again."
    echo "Or add $DIRECTIVE to the global Nginx config and restart Nginx."
    exit 1
  fi
fi

if grep -q 'client_max_body_size' "$NGINX_FILE"; then
  echo "client_max_body_size already present in $NGINX_FILE"
else
  # Insert after first "server {" (macOS sed)
  sed -i '' "/server {/a\\
    $DIRECTIVE;
" "$NGINX_FILE"
  echo "Added $DIRECTIVE to $NGINX_FILE"
fi

echo "Restart Nginx from Herd (or run: herd restart nginx) for changes to take effect."
