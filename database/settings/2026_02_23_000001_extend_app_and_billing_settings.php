<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $addIfMissing = function (string $key, mixed $value): void {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        };

        $addIfMissing('app.locale', config('app.locale', 'en'));
        $addIfMissing('app.fallback_locale', config('app.fallback_locale', 'en'));
        $addIfMissing('billing.default_gateway', config('billing.default_gateway', 'stripe'));
        $addIfMissing('billing.currency', config('billing.currency', 'usd'));
        $addIfMissing('billing.trial_days', (int) config('billing.trial_days', 14));
        $addIfMissing('billing.credit_expiration_days', (int) config('billing.credit_expiration_days', 365));
        $addIfMissing('billing.dunning_intervals', config('billing.dunning_intervals', [3, 7, 14]));
        $addIfMissing('billing.geo_restriction_enabled', (bool) config('billing.geo_restriction_enabled', false));
        $addIfMissing('billing.geo_blocked_countries', config('billing.geo_blocked_countries', []));
        $addIfMissing('billing.geo_allowed_countries', config('billing.geo_allowed_countries', []));
    }
};
