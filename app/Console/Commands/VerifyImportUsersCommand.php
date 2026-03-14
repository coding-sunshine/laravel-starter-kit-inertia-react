<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class VerifyImportUsersCommand extends Command
{
    protected $signature = 'fusion:verify-import-users
                            {--expected-users=1483 : Expected total user count in PostgreSQL}';

    protected $description = 'Verify the fusion:import-users command results.';

    public function handle(): int
    {
        $this->info('=== fusion:verify-import-users ===');

        $expectedUsers = (int) $this->option('expected-users');

        $pass = true;

        // Count users in new DB
        $totalUsers = DB::table('users')->count();
        $usersWithContact = DB::table('users')->whereNotNull('contact_id')->count();
        $usersWithoutContact = DB::table('users')->whereNull('contact_id')->count();

        // Count subscriber orgs
        $subscriberOrgs = DB::table('organizations')->count();

        // Contact map completeness
        $totalContacts = DB::table('contacts')->whereNotNull('legacy_lead_id')->count();
        $mappedUsers = $usersWithContact;

        // Check for broken FKs (contact_id not in contacts table)
        $brokenFks = DB::table('users')
            ->whereNotNull('contact_id')
            ->whereNotIn('contact_id', DB::table('contacts')->pluck('id'))
            ->count();

        // Report
        $this->reportCheck(
            "users: {$totalUsers} rows",
            $totalUsers >= $expectedUsers,
            $totalUsers >= $expectedUsers ? null : "expected >= {$expectedUsers}",
            $pass
        );

        $this->reportCheck(
            "users with contact_id linked: {$usersWithContact}",
            $usersWithContact > 0,
            $usersWithContact > 0 ? null : 'no users linked to contacts',
            $pass
        );

        $this->reportCheck(
            "users without contact_id (expected — no lead_id): {$usersWithoutContact}",
            true
        );

        $this->reportCheck(
            "organizations created: {$subscriberOrgs}",
            $subscriberOrgs > 0,
            $subscriberOrgs > 0 ? null : 'no subscriber organizations found',
            $pass
        );

        $this->reportCheck(
            "broken FK (contact_id not in contacts): {$brokenFks}",
            $brokenFks === 0,
            $brokenFks > 0 ? "{$brokenFks} broken foreign keys found" : null,
            $pass
        );

        $this->newLine();

        if ($pass) {
            $this->info('OVERALL: PASS ✅');

            return self::SUCCESS;
        }

        $this->error('OVERALL: FAIL ❌');

        return self::FAILURE;
    }

    private function reportCheck(string $label, bool $ok, ?string $reason = null, bool &$pass = true): void
    {
        if ($ok) {
            $this->line("✅ {$label}");
        } else {
            $this->line("❌ {$label}".($reason ? " — {$reason}" : ''));
            $pass = false;
        }
    }
}
