<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class SeoSettings extends Settings
{
    public string $meta_title;

    public string $meta_description;

    public ?string $og_image = null;

    public static function group(): string
    {
        return 'seo';
    }
}
