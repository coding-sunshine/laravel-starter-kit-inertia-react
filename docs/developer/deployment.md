# Deployment Guide

This guide covers deploying the Laravel + Inertia React application to production: environment configuration, assets, caching, and hardening.

## Pre-deployment checklist

- [ ] Tests passing: `php artisan test`
- [ ] Code formatted: `vendor/bin/pint`
- [ ] Production env vars set (see below)
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Database migrations tested (e.g. in staging)
- [ ] HTTPS and secure cookies in production

## Environment configuration

### Critical production settings

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# Use a strong app key (generate with php artisan key:generate)
APP_KEY=base64:...
```

### Session and cache

Use `database` or `redis` for session and cache in production (avoid `file` for multi-server). Example:

```bash
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

For Redis:

```bash
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Optional: IP whitelist for admin

To restrict Filament (or other routes) to specific IPs, set:

```bash
IP_WHITELIST=203.0.113.10,198.51.100.0/24
```

Then apply the `ip.whitelist` middleware to the relevant route group or Filament panel. See [Middleware](#middleware) below.

## Asset compilation

```bash
npm ci
npm run build
```

Ensure `APP_URL` in `.env` matches the production domain so Vite-generated asset URLs are correct. Build output goes to `public/build/`.

## Caching

After deployment, run:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Clear caches when you change config, routes, or views:

```bash
php artisan optimize:clear
# or individually: config:clear, route:clear, view:clear, cache:clear
```

## Queue and scheduler

If the app uses queues (e.g. personal data export, notifications):

- Run a queue worker: `php artisan queue:work` (or use Supervisor).
- Restart workers after deploy: `php artisan queue:restart`.

For scheduled tasks (e.g. `personal-data-export:clean`), add to the server crontab:

```bash
* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

## Security hardening

- **HTTPS**: Enforce TLS; set `APP_URL` to `https://`.
- **Cookies**: In production, ensure `SESSION_SECURE_COOKIE` and secure cookie options are enabled where applicable.
- **Headers**: The app uses `AdditionalSecurityHeaders` and CSP (Spatie); keep them enabled.
- **Admin IP restriction**: Use `IP_WHITELIST` and the `ip.whitelist` middleware on the Filament panel or admin route group when required.

## Middleware

- **EnforceIpWhitelist** (`ip.whitelist`): Restricts access by IP when `config('app.ip_whitelist')` is non-empty. Apply to route groups or Filament panel as needed.
- **ThrottleTwoFactorManagement**: Applied globally to web routes; rate-limits 2FA management endpoints (5 requests per minute per user).

## Composer

Production install:

```bash
composer install --optimize-autoloader --no-dev
```

## Troubleshooting

- **Vite manifest missing**: Run `npm run build` and ensure `public/build` is deployed.
- **403 on admin**: If using `ip.whitelist`, ensure your IP is in `IP_WHITELIST` or the middleware is not applied to that route.
- **Queued jobs not running**: Start a queue worker and ensure `QUEUE_CONNECTION` is not `sync` in production.
