<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('seo.meta_title', config('app.name', ''));
        $this->migrator->add('seo.meta_description', '');
        $this->migrator->add('seo.og_image');
    }
};
