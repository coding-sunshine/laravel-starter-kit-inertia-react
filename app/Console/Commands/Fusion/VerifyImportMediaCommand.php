<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class VerifyImportMediaCommand extends Command
{
    protected $signature = 'fusion:verify-import-media
                            {--legacy=mysql_legacy : Legacy DB connection to compare counts}';

    protected $description = 'Verify media import: compare row counts and spot-check model_type mappings.';

    public function handle(): int
    {
        $conn = $this->option('legacy');

        $mediaCount = (int) DB::table('media')->count();
        $legacyCount = $this->legacyCount($conn);

        $this->table(
            ['Metric', 'New (PostgreSQL)', 'Legacy (MySQL)', 'OK?'],
            [
                ['media', (string) $mediaCount, $legacyCount, $this->cmp($mediaCount, $legacyCount)],
            ]
        );

        // Spot-check model_type mappings
        $this->info('Model type distribution (new DB):');
        $types = DB::table('media')
            ->select('model_type', DB::raw('count(*) as cnt'))
            ->groupBy('model_type')
            ->orderByDesc('cnt')
            ->get();

        foreach ($types as $type) {
            $this->line("  {$type->model_type}: {$type->cnt}");
        }

        // Check no legacy Lead types remain
        $legacyLeadCount = DB::table('media')
            ->where('model_type', 'App\\Models\\Lead')
            ->count();

        if ($legacyLeadCount > 0) {
            $this->warn("Warning: {$legacyLeadCount} media records still have model_type 'App\\Models\\Lead'.");
        } else {
            $this->info('All Lead model_types have been mapped to Contact.');
        }

        $legacyInt = $this->legacyCountInt($conn);
        $ok = $legacyInt !== null && $mediaCount >= (int) ($legacyInt * 0.95);

        if (! $ok) {
            $this->warn('Verification: media counts may not match. Expected ~107,181.');

            return self::FAILURE;
        }

        $this->info('Verification PASS: media records imported.');

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
            return (int) DB::connection($connection)->table('media')->count();
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

        return $a === (int) $b ? '✓' : '≈';
    }
}
