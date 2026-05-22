<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\BuildManagerBrief;
use App\Models\Siding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Rebuilds the Manager Brief payload for one or all active sidings and writes
 * the result to the application cache.
 *
 * Cache key format : "manager-brief:{sidingId}:v1"
 * TTL              : 90 minutes (5 400 seconds)
 *
 * The command is intentionally fault-tolerant: a failure for one siding logs a
 * WARNING and continues to the next rather than aborting the whole run.
 */
final class RefreshManagerBriefCommand extends Command
{
    private const CACHE_TTL_SECONDS = 5_400; // 90 minutes

    protected $signature = 'manager-brief:refresh
                            {--siding= : Optional siding ID; when omitted all active sidings are processed}';

    protected $description = 'Rebuild the Manager Brief payload for one or all active sidings and write to cache';

    public function handle(): int
    {
        $sidingOption = $this->option('siding');

        $sidings = $sidingOption !== null
            ? $this->resolveSingleSiding((int) $sidingOption)
            : $this->resolveAllSidings();

        /** @var BuildManagerBrief $build */
        $build = app(BuildManagerBrief::class);

        $processed = 0;
        $failed = 0;

        foreach ($sidings as $sidingId) {
            try {
                $payload = $build->handle($sidingId);

                Cache::put(
                    "manager-brief:{$sidingId}:v1",
                    $payload->toArray(),
                    self::CACHE_TTL_SECONDS,
                );

                $processed++;
            } catch (Throwable $e) {
                $failed++;

                Log::warning('manager-brief:refresh failed for siding', [
                    'siding_id' => $sidingId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('manager-brief:refresh completed', [
            'sidings_processed' => $processed,
            'failed_count' => $failed,
        ]);

        return self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    private function resolveAllSidings(): array
    {
        return Siding::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function resolveSingleSiding(int $sidingId): array
    {
        return [$sidingId];
    }
}
