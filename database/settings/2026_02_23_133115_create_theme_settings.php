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

        $addIfMissing('theme.preset', config('theme.preset', 'default'));
        $addIfMissing('theme.base_color', config('theme.base_color', 'neutral'));
        $addIfMissing('theme.radius', config('theme.radius', 'default'));
        $addIfMissing('theme.font', config('theme.font', 'instrument-sans'));
        $addIfMissing('theme.default_appearance', config('theme.default_appearance', 'system'));
    }
};
