<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class VerifyImportUsersCommand extends Command
{
    protected $signature = 'fusion:verify-import-users
                            {--legacy=mysql_legacy : Legacy DB connection to compare counts}';

    protected $description = 'Verify user import: compare row counts and check contact_id linkage.';

    public function handle(): int
    {
        $conn = $this->option('legacy');

        $usersCount = User::count();
        $withContactId = User::whereNotNull('contact_id')->count();
        $legacyCount = $this->legacyCount($conn);

        $this->table(
            ['Metric', 'New (PostgreSQL)', 'Legacy (MySQL)', 'OK?'],
            [
                ['users', (string) $usersCount, $legacyCount, $this->cmp($usersCount, $legacyCount)],
            ]
        );

        $this->line('Users with contact_id set: '.$withContactId);

        $legacyInt = $this->legacyCountInt($conn);
        $ok = $legacyInt !== null && $usersCount === $legacyInt;

        if (! $ok) {
            $this->warn('Verification: user counts do not match. Expected ~1,483.');

            return self::FAILURE;
        }

        $this->info('Verification PASS: users fully imported.');

        return self::SUCCESS;
    }

    private function legacyCount(string $connection): string
    {
        $n = $this->legacyCountInt($connection);

        return $n === null ? 'N/A (no connection)' : (string) $n;
    }

    private function legacyCountInt(string $connection): ?int
    {
        try {
            return (int) DB::connection($connection)->table('users')->count();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  int|string  $b
     */
    private function cmp(int $a, $b): string
    {
        if ($b === 'N/A (no connection)' || ! is_numeric($b)) {
            return '—';
        }

        return $a === (int) $b ? '✓' : '✗';
    }
}
