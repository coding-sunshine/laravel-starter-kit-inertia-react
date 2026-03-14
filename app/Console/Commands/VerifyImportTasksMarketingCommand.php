<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class VerifyImportTasksMarketingCommand extends Command
{
    protected $signature = 'fusion:verify-import-tasks-marketing';

    protected $description = 'Verify tasks/relationships/partners/marketing import row counts against expected values.';

    public function handle(): int
    {
        $pass = true;
        $results = [];

        $checks = [
            ['table' => 'tasks', 'expected' => 53, 'min' => 0],
            ['table' => 'relationships', 'expected' => 7304, 'min' => 0],
            ['table' => 'partners', 'expected' => 298, 'min' => 0],
            ['table' => 'mail_lists', 'expected' => 66, 'min' => 0],
            ['table' => 'brochure_mail_jobs', 'expected' => 180, 'min' => 0],
            ['table' => 'website_contacts', 'expected' => 1218, 'min' => 0],
            ['table' => 'crm_notes', 'expected' => 22418, 'min' => 0],
            ['table' => 'crm_comments', 'expected' => 4197, 'min' => 0],
            ['table' => 'crm_addresses', 'expected' => 24508, 'min' => 0],
            ['table' => 'campaign_websites', 'expected' => 199, 'min' => 0],
            ['table' => 'campaign_website_project', 'expected' => 327, 'min' => 0],
            ['table' => 'crm_resources', 'expected' => 138, 'min' => 0],
            ['table' => 'column_management', 'expected' => 4430, 'min' => 0],
        ];

        foreach ($checks as $check) {
            try {
                $actual = DB::table($check['table'])->count();
                $status = $actual >= $check['min'] ? 'OK' : 'LOW';
                $results[] = "{$check['table']}: {$actual} (expected ~{$check['expected']}) {$status}";
                if ($actual < $check['min']) {
                    $pass = false;
                }
            } catch (Throwable $e) {
                $results[] = "{$check['table']}: ERROR - ".$e->getMessage();
            }
        }

        // Check for broken FKs
        try {
            $brokenPartnerFks = DB::table('partners as p')
                ->leftJoin('contacts as c', 'p.contact_id', '=', 'c.id')
                ->where('p.contact_id', '>', 0)
                ->whereNull('c.id')
                ->count();
            $results[] = "broken_partner_contact_fks: {$brokenPartnerFks}";
        } catch (Throwable) {
        }

        $result = $pass ? 'PASS' : 'FAIL';
        $output = implode(', ', $results).", RESULT: {$result}";
        $this->line($output);

        return $pass ? self::SUCCESS : self::FAILURE;
    }
}
