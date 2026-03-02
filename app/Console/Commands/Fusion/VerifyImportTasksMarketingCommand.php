<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class VerifyImportTasksMarketingCommand extends Command
{
    protected $signature = 'fusion:verify-import-tasks-marketing';

    protected $description = 'Verify Step 5 imports (tasks, relationships, partners, notes, comments, addresses, statusables, campaign_*, resources, column_management, etc.) against legacy MySQL counts.';

    public function handle(): int
    {
        $connection = 'mysql_legacy';

        $rows = [
            ['tasks', 53],
            ['relationships', 7304],
            ['partners', 298],
            ['mail_lists', 66],
            ['brochure_mail_jobs', 180],
            ['mail_job_statuses', 180],
            ['website_contacts', 1218],
            ['notes', 22418],
            ['comments', 4197],
            ['addresses', 24508],
            ['statusables', 147913],
            ['statuses', 214],
            ['campaign_websites', 199],
            ['campaign_website_project', 327],
            ['resources', 138],
            ['column_management', 4430],
        ];

        $this->table(
            ['Table', 'New (PostgreSQL)', 'Legacy (MySQL)', 'OK?'],
            collect($rows)->map(function (array $row) use ($connection): array {
                [$table, $expected] = $row;

                $new = DB::table($table)->count();
                $legacy = DB::connection($connection)->table($table)->count();

                return [
                    $table,
                    $new,
                    $legacy,
                    $new === $legacy ? '✓' : '✗',
                ];
            })->all(),
        );

        return self::SUCCESS;
    }
}

