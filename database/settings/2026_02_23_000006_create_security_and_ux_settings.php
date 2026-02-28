<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $addIfMissing = function (string $key, mixed $value, bool $encrypted = false): void {
            if (! $this->migrator->exists($key)) {
                $encrypted ? $this->migrator->addEncrypted($key, $value) : $this->migrator->add($key, $value);
            }
        };

        // Security
        $addIfMissing('security.csp_enabled', (bool) config('csp.enabled', true));
        $addIfMissing('security.csp_nonce_enabled', (bool) config('csp.nonce_enabled', false));
        $addIfMissing('security.csp_report_uri', config('csp.report_uri', ''));
        $addIfMissing('security.honeypot_enabled', (bool) config('honeypot.enabled', true));
        $addIfMissing('security.honeypot_seconds', (int) config('honeypot.amount_of_seconds', 1));
        $addIfMissing('security.ip_whitelist', []);

        // Cookie Consent
        $addIfMissing('cookie-consent.enabled', (bool) config('cookie-consent.enabled', true));

        // Performance
        $addIfMissing('performance.cache_enabled', (bool) config('responsecache.enabled', false));
        $addIfMissing('performance.cache_lifetime_seconds', (int) config('responsecache.cache_lifetime_in_seconds', 604800));
        $addIfMissing('performance.cache_driver', config('responsecache.cache_store', 'file'));

        // Monitoring
        $addIfMissing('monitoring.sentry_dsn', config('sentry.dsn'), true);
        $addIfMissing('monitoring.sentry_sample_rate', (float) config('sentry.sample_rate', 1.0));
        $addIfMissing('monitoring.sentry_traces_sample_rate', config('sentry.traces_sample_rate'));
        $addIfMissing('monitoring.telescope_enabled', (bool) config('telescope.enabled', true));

        // Feature Flags
        $addIfMissing('feature-flags.globally_disabled_modules', config('feature-flags.globally_disabled', []));
    }
};
