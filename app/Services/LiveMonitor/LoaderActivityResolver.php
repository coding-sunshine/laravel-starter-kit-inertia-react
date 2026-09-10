<?php

declare(strict_types=1);

namespace App\Services\LiveMonitor;

use App\Models\Loader;
use App\Models\RakeWagonLoading;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Derives per-loader activity from recent wagon-loading rows.
 *
 * Important caveat: this is a POST-HOC proxy, not a true realtime feed. The DB does
 * not capture "loader currently positioned at wagon X" — it only captures, post-hoc,
 * which loader filled which wagon. We approximate "active loader" as: the loader
 * whose most recent wagon_loading row was updated within the activity window AND
 * whose target wagon is not yet fully loaded.
 *
 * When Loadrite live ingestion lands (v2), the source for "current wagon" should
 * switch to Loadrite's Loading event stream. This service's public shape stays
 * stable so the UI does not change.
 */
final class LoaderActivityResolver
{
    /** Window beyond which a loader is considered idle. */
    public const ACTIVITY_WINDOW_MINUTES = 10;

    /**
     * @param  Collection<int, Loader>  $loaders  loaders that may work the rake (by siding membership)
     * @param  Collection<int, RakeWagonLoading>  $loadings  wagon_loading rows for the rake (with wagon relation eager-loaded)
     * @return array<int, array{
     *     loader_id: int,
     *     loader_name: string,
     *     loader_code: string|null,
     *     status: string,
     *     current_wagon_id: int|null,
     *     current_wagon_number: string|null,
     *     last_loaded_mt: float|null,
     *     last_active_at: string|null,
     *     wagons_completed: int
     * }>
     */
    public function resolveForRake(Collection $loaders, Collection $loadings): array
    {
        $threshold = CarbonImmutable::now()->subMinutes(self::ACTIVITY_WINDOW_MINUTES);

        $byLoader = $loadings
            ->whereNotNull('loader_id')
            ->groupBy('loader_id');

        $rows = [];
        foreach ($loaders as $loader) {
            $loaderRows = $byLoader->get($loader->id, collect());

            $latestRow = $loaderRows->sortByDesc('updated_at')->first();
            $lastActiveAt = $latestRow?->updated_at;

            $isActive = $lastActiveAt !== null
                && CarbonImmutable::parse($lastActiveAt)->greaterThanOrEqualTo($threshold);

            $currentWagonId = null;
            $currentWagonNumber = null;
            $lastLoadedMt = null;

            if ($isActive && $latestRow !== null) {
                $currentWagonId = (int) $latestRow->wagon_id;
                $currentWagonNumber = $latestRow->wagon?->wagon_number;
                $lastLoadedMt = $latestRow->loaded_quantity_mt !== null
                    ? (float) $latestRow->loaded_quantity_mt
                    : null;
            }

            $wagonsCompleted = $loaderRows
                ->filter(fn (RakeWagonLoading $r): bool => (float) ($r->loaded_quantity_mt ?? 0) > 0)
                ->count();

            $rows[] = [
                'loader_id' => (int) $loader->id,
                'loader_name' => (string) $loader->loader_name,
                'loader_code' => $loader->code,
                'status' => $isActive ? 'active' : 'idle',
                'current_wagon_id' => $currentWagonId,
                'current_wagon_number' => $currentWagonNumber,
                'last_loaded_mt' => $lastLoadedMt,
                'last_active_at' => $lastActiveAt?->toIso8601String(),
                'wagons_completed' => $wagonsCompleted,
            ];
        }

        usort($rows, function (array $a, array $b): int {
            if ($a['status'] !== $b['status']) {
                return $a['status'] === 'active' ? -1 : 1;
            }

            return strcmp((string) $a['loader_name'], (string) $b['loader_name']);
        });

        return $rows;
    }
}
