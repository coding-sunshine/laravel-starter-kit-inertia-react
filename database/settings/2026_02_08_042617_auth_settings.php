<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('auth.registration_enabled', true);
        $this->migrator->add('auth.email_verification_required', false);
    }
};
