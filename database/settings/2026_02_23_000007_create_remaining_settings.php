<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $addIfMissing = function (string $key, mixed $value, bool $encrypted = false): void {
            if (! $this->migrator->exists($key)) {
                $encrypted ? $this->migrator->addEncrypted($key, $value) : $this->migrator->add($key, $value);
            }
        };

        // Filesystem
        $addIfMissing('filesystem.default_disk', config('filesystems.default', 'local'));
        $addIfMissing('filesystem.s3_key', config('filesystems.disks.s3.key'), true);
        $addIfMissing('filesystem.s3_secret', config('filesystems.disks.s3.secret'), true);
        $addIfMissing('filesystem.s3_region', config('filesystems.disks.s3.region', 'us-east-1'));
        $addIfMissing('filesystem.s3_bucket', config('filesystems.disks.s3.bucket'));
        $addIfMissing('filesystem.s3_url', config('filesystems.disks.s3.url'));

        // Broadcasting
        $addIfMissing('broadcasting.reverb_app_id', config('reverb.apps.apps.0.app_id'));
        $addIfMissing('broadcasting.reverb_app_key', config('reverb.apps.apps.0.key'));
        $addIfMissing('broadcasting.reverb_app_secret', config('reverb.apps.apps.0.secret'), true);
        $addIfMissing('broadcasting.reverb_host', config('reverb.servers.reverb.host', 'localhost'));
        $addIfMissing('broadcasting.reverb_port', (int) config('reverb.servers.reverb.port', 8080));
        $addIfMissing('broadcasting.reverb_scheme', config('reverb.servers.reverb.hostname') ? 'https' : 'http');

        // Permission
        $addIfMissing('permission.teams_enabled', (bool) config('permission.teams', true));
        $addIfMissing('permission.team_foreign_key', config('permission.team_foreign_key', 'organization_id'));

        // Activity Log
        $addIfMissing('activitylog.enabled', (bool) config('activitylog.enabled', true));
        $addIfMissing('activitylog.delete_records_older_than_days_enabled', false);
        $addIfMissing('activitylog.delete_records_older_than_days', 365);

        // Impersonate
        $addIfMissing('impersonate.enabled', true);
        $addIfMissing('impersonate.banner_style', config('filament-impersonate.banner.style', 'dark'));

        // Backup
        $addIfMissing('backup.name', config('backup.backup.name', 'laravel-backup'));
        $addIfMissing('backup.keep_all_backups_for_days', (int) config('backup.cleanup.default_strategy.keep_all_backups_for_days', 7));
        $addIfMissing('backup.keep_daily_backups_for_days', (int) config('backup.cleanup.default_strategy.keep_daily_backups_for_days', 16));
        $addIfMissing('backup.keep_weekly_backups_for_weeks', (int) config('backup.cleanup.default_strategy.keep_weekly_backups_for_weeks', 8));
        $addIfMissing('backup.keep_monthly_backups_for_months', (int) config('backup.cleanup.default_strategy.keep_monthly_backups_for_months', 4));
        $addIfMissing('backup.keep_yearly_backups_for_years', (int) config('backup.cleanup.default_strategy.keep_yearly_backups_for_years', 2));
        $addIfMissing('backup.delete_oldest_when_size_mb', (int) config('backup.cleanup.default_strategy.delete_oldest_backups_when_using_more_megabytes_than', 5000));

        // Media
        $addIfMissing('media.disk_name', config('media-library.disk_name', 'public'));
        $addIfMissing('media.max_file_size', (int) config('media-library.max_file_size', 1024 * 1024 * 200));
    }
};
