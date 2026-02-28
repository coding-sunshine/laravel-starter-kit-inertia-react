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

        // AiSettings extensions — Cohere and Jina API keys (not covered by PrismSettings)
        $addIfMissing('ai.cohere_api_key', config('ai.providers.cohere.key'), true);
        $addIfMissing('ai.jina_api_key', config('ai.providers.jina.key'), true);

        // TenancySettings extensions — enabled, domain, subdomain_resolution
        $addIfMissing('tenancy.enabled', config('tenancy.enabled', true));
        $addIfMissing('tenancy.domain', config('tenancy.domain'));
        $addIfMissing('tenancy.subdomain_resolution', config('tenancy.subdomain_resolution', true));
    }
};
