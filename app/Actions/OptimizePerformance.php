<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Models\Siding;
use App\Models\StockLedger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * OptimizePerformance - Query optimization and caching strategies
 *
 * Handles:
 * - N+1 query prevention via eager loading
 * - Strategic query caching with TTL
 * - Index-aware query patterns
 * - Batch operation optimization
 */
final readonly class OptimizePerformance
{
    /**
     * Get sidingmetrics with optimized queries and caching
     */
    public function getSidingMetricsCached(int $sidingId, int $cacheTtlSeconds = 300): array
    {
        $cacheKey = "siding.metrics.{$sidingId}";

        return Cache::remember($cacheKey, $cacheTtlSeconds, fn (): array => $this->calculateSidingMetrics($sidingId));
    }

    /**
     * Load rakes with all relationships using eager loading
     */
    public function loadRakesOptimized(int $sidingId): Collection
    {
        return Rake::query()->where('siding_id', $sidingId)
            ->with([
                'guardInspection',           // One-to-one: no N+1
                'weighments' => fn ($q) => $q->latest('weighment_time')->limit(3),
                'rrDocument' => fn ($q) => $q->latest('created_at'),
                'penalties' => fn ($q) => $q->where('penalty_status', '!=', 'waived'),
                'createdBy:id,name',         // Select only needed columns
                'siding:id,name,code',
            ])
            ->select(['id', 'siding_id', 'rake_number', 'state', 'loaded_weight_mt', 'created_by', 'created_at'])
            ->get();
    }

    /**
     * Batch insert optimizations
     */
    public function batchInsertPenalties(array $penaltyData): int
    {
        // Split into chunks to avoid memory issues
        $chunks = array_chunk($penaltyData, 100);
        $inserted = 0;

        foreach ($chunks as $chunk) {
            $inserted += DB::table('penalties')->insertOrIgnore($chunk);
        }

        return $inserted;
    }

    /**
     * Batch update optimizations
     */
    public function batchUpdateRakeStates(array $rakeStateMap): int
    {
        // Use raw SQL for better performance on large batches
        $updated = 0;

        DB::transaction(function () use ($rakeStateMap, &$updated): void {
            foreach ($rakeStateMap as $rakeId => $state) {
                $updated += DB::table('rakes')
                    ->where('id', $rakeId)
                    ->update(['state' => $state, 'updated_at' => now()]);
            }
        });

        return $updated;
    }

    /**
     * Clear performance caches
     */
    public function clearCaches(int $sidingId): void
    {
        $keys = [
            "siding.metrics.{$sidingId}",
            "siding.rakes.{$sidingId}",
            "siding.stock.{$sidingId}",
            "siding.demurrage.{$sidingId}",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Generate query performance report
     */
    public function analyzeQueryPerformance(): array
    {
        // Enable query log
        DB::enableQueryLog();

        $startTime = microtime(true);

        // Run common queries
        Siding::all();
        Rake::with('weighments', 'guardInspection', 'penalties')->get();
        StockLedger::query()->latest('created_at')->limit(100)->get();

        $endTime = microtime(true);
        $queries = DB::getQueryLog();

        return [
            'total_queries' => count($queries),
            'execution_time_ms' => round(($endTime - $startTime) * 1000, 2),
            'slow_queries' => array_filter($queries, fn (array $q): bool => $q['time'] > 100),
            'recommendations' => $this->generateOptimizationRecommendations($queries),
        ];
    }

    /**
     * Index health check
     */
    public function checkIndexHealth(): array
    {
        $tables = [
            'rakes' => ['siding_id', 'state', 'rake_number'],
            'penalties' => ['rake_id', 'status', 'charge_date'],
            'stock_ledgers' => ['siding_id', 'created_at', 'transaction_type'],
            'indents' => ['siding_id', 'state', 'required_by_date'],
        ];

        $status = [];

        foreach ($tables as $table => $recommendedIndexes) {
            $existingIndexes = DB::select("SHOW INDEX FROM {$table}");
            $indexedColumns = array_column($existingIndexes, 'Column_name');

            $status[$table] = [
                'recommended' => $recommendedIndexes,
                'existing' => $indexedColumns,
                'missing' => array_diff($recommendedIndexes, $indexedColumns),
                'health' => count(array_diff($recommendedIndexes, $indexedColumns)) === 0 ? 'healthy' : 'needs_indexes',
            ];
        }

        return $status;
    }

    /**
     * Calculate siding metrics with optimized queries
     */
    private function calculateSidingMetrics(int $sidingId): array
    {
        // Single query with aggregation instead of loading collections
        $metrics = DB::table('rakes')
            ->where('siding_id', $sidingId)
            ->selectRaw('
                COUNT(*) as total_rakes,
                SUM(CASE WHEN state = \'delivered\' THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN state = \'in_transit\' THEN 1 ELSE 0 END) as in_transit_count,
                SUM(CASE WHEN state = \'staged\' THEN 1 ELSE 0 END) as staged_count,
                SUM(CASE WHEN state = \'loading\' THEN 1 ELSE 0 END) as loading_count,
                SUM(loaded_weight_mt) as total_weight_mt,
                AVG(loaded_weight_mt) as avg_weight_mt
            ')
            ->first();

        // Stock levels in single query
        $lastStock = StockLedger::query()->where('siding_id', $sidingId)
            ->latest('created_at')
            ->first();

        // Demurrage in single aggregated query
        $demurrage = DB::table('penalties')
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->where('rakes.siding_id', $sidingId)
            ->selectRaw('
                SUM(amount) as total,
                SUM(CASE WHEN status = \'collected\' THEN amount ELSE 0 END) as collected,
                SUM(CASE WHEN status = \'pending\' THEN amount ELSE 0 END) as pending,
                COUNT(*) as count
            ')
            ->first();

        // Indent metrics in single query
        $indents = DB::table('indents')
            ->where('siding_id', $sidingId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN state = \'fulfilled\' THEN 1 ELSE 0 END) as fulfilled,
                SUM(CASE WHEN state = \'partial\' THEN 1 ELSE 0 END) as partial,
                SUM(target_quantity_mt) as total_requested,
                SUM(allocated_quantity_mt) as total_allocated
            ')
            ->first();

        return [
            'siding_id' => $sidingId,
            'rakes' => [
                'total' => $metrics->total_rakes ?? 0,
                'delivered' => $metrics->delivered_count ?? 0,
                'in_transit' => $metrics->in_transit_count ?? 0,
                'staged' => $metrics->staged_count ?? 0,
                'loading' => $metrics->loading_count ?? 0,
            ],
            'stock' => [
                'current_balance_mt' => $lastStock?->closing_balance_mt ?? 0,
                'last_updated' => $lastStock?->created_at,
            ],
            'demurrage' => [
                'total' => $demurrage->total ?? 0,
                'collected' => $demurrage->collected ?? 0,
                'pending' => $demurrage->pending ?? 0,
                'count' => $demurrage->count ?? 0,
            ],
            'indents' => [
                'total' => $indents->total ?? 0,
                'fulfilled' => $indents->fulfilled ?? 0,
                'partial' => $indents->partial ?? 0,
                'fulfillment_rate' => $indents->total_requested > 0
                    ? round(($indents->total_allocated / $indents->total_requested) * 100, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Generate optimization recommendations
     */
    private function generateOptimizationRecommendations(array $queries): array
    {
        $recommendations = [];

        // Check for N+1 patterns
        $selectQueries = array_filter($queries, fn (array $q): bool => str_contains((string) $q['query'], 'SELECT'));
        if (count($selectQueries) > 10) {
            $recommendations[] = 'High query count detected - Consider implementing caching';
        }

        // Check for slow queries
        $slowQueries = array_filter($queries, fn (array $q): bool => $q['time'] > 100);
        if ($slowQueries !== []) {
            $recommendations[] = 'Slow queries detected - Add database indexes or optimize query conditions';
        }

        // Check for missing indexes
        $recommendations[] = 'Ensure indexes exist on: rakes(siding_id, state), penalties(rake_id, status)';
        $recommendations[] = 'Use eager loading: with(\'relation\') instead of lazy loading';

        return $recommendations;
    }
}
