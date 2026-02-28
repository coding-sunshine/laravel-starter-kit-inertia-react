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

        $addIfMissing('setup-wizard.setup_completed', false);
        $addIfMissing('setup-wizard.completed_steps', []);
    }
};
