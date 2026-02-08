<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class AppSettings extends Settings
{
    public string $site_name;

    public bool $maintenance_mode;

    public string $timezone;

    public static function group(): string
    {
        return 'app';
    }
}
