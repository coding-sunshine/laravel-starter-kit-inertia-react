# Composer path packages (Laravel 13)

These directories are **vendored forks** of upstream packages whose Packagist releases do not yet declare `illuminate/*` **^13.0**. Source is copied from the upstream GitHub default branch; only `composer.json` was changed to widen constraints (and PHP where needed).

| Package | Upstream | Notes |
|--------|----------|--------|
| `beyondcode-laravel-vouchers` | [beyondcode/laravel-vouchers](https://github.com/beyondcode/laravel-vouchers) | Version `2.3.1` — drop this fork when upstream merges Laravel 13 support. |
| `jijunair-laravel-referral` | [jijunair/laravel-referral](https://github.com/jijunair/laravel-referral) | Version `1.0.5` — drop when upstream adds `^13.0`. |
| `eznix86-laravel-ai-memory` | [eznix86/laravel-ai-memory](https://github.com/eznix86/laravel-ai-memory) | Version `1.0.2` — widens `laravel/ai` to `^0.6`; drop when upstream supports it. |
| `laravelcm-laravel-subscriptions` | [laravelcm/laravel-subscriptions](https://github.com/laravelcm/laravel-subscriptions) | Version `1.8.1` — allows `spatie/laravel-sluggable` **^4** and aligns `HasSlug` with Spatie v4; drop when upstream merges. |

Root `composer.json` uses `repositories` type `path` to prefer these over Packagist.
