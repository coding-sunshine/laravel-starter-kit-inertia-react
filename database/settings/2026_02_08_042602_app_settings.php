<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('app.site_name', config('app.name', 'Laravel'));
        $this->migrator->add('app.maintenance_mode', false);
        $this->migrator->add('app.timezone', config('app.timezone', 'UTC'));
    }
};
