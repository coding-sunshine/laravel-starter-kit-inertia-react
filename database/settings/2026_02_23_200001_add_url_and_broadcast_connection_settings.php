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

        // AppSettings — application URL (was APP_URL env var)
        $addIfMissing('app.url', config('app.url', 'http://localhost'));

        // BroadcastingSettings — default connection (was BROADCAST_CONNECTION env var)
        $addIfMissing('broadcasting.default_connection', config('broadcasting.default', 'log'));
    }
};
