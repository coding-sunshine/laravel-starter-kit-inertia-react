<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /** 200 MB in bytes (for defect/incident/claim photos). */
    private const int MAX_FILE_SIZE_200MB = 209715200;

    /** Previous default was 10 KB; raise any installation still using it. */
    private const int OLD_SMALL_LIMIT = 10240;

    public function up(): void
    {
        $key = 'media.max_file_size';
        if (! $this->migrator->exists($key)) {
            return;
        }
        $this->migrator->update($key, function (mixed $current): int {
            $size = (int) $current;

            return $size <= self::OLD_SMALL_LIMIT ? self::MAX_FILE_SIZE_200MB : $size;
        });
    }
};
