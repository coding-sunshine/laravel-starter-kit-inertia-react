<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class MediaSettings extends Settings
{
    public string $disk_name = 'public';

    /** Max file size in bytes (default 200 MB for defect/incident/claim photos). */
    public int $max_file_size = 209715200;

    public static function group(): string
    {
        return 'media';
    }
}
