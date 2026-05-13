<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\WagonWeightUpdated;
use App\Http\Integrations\Loadrite\Requests\GetLoadingEventsRequest;
use App\Models\LoadriteSetting;
use App\Models\Rake;
use App\Models\WagonLoading;
use App\Services\LoadriteTokenManager;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

final class LoadriteBackfillCommand extends Command
{
    protected $signature = 'loadrite:backfill
                            {--from= : Start date (Y-m-d), default 7 days ago, max 31 days back}
                            {--to=   : End date (Y-m-d), default today}
                            {--siding= : Specific siding ID, default all configured sidings}
                            {--dry-run : Fetch and count events without writing to DB}';

    protected $description = 'Backfill historical Loadrite weight events (up to 31 days) into wagon loadings.';

    public function handle(LoadriteTokenManager $tokenManager): int
    {
        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))->startOfDay()
            : now()->subMonths(2)->startOfDay();

        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))->endOfDay()
            : now()->endOfDay();

        $maxFrom = now()->subDays(31)->startOfDay();

        if ($from->lt($maxFrom)) {
            $this->warn('Loadrite only keeps 31 days. Clamping --from to '.$maxFrom->toDateString().'.');
            $from = $maxFrom;
        }

        $dryRun = (bool) $this->option('dry-run');
        $sidingOption = $this->option('siding') ? (int) $this->option('siding') : null;

        $settings = LoadriteSetting::query()
            ->whereNotNull('siding_id')
            ->when($sidingOption, fn ($q) => $q->where('siding_id', $sidingOption))
            ->get();

        if ($settings->isEmpty()) {
            $this->error('No loadrite_settings found. Run loadrite:store-token first.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            '%sBackfilling %s → %s for %d siding(s).',
            $dryRun ? '[DRY RUN] ' : '',
            $from->toDateTimeString(),
            $to->toDateTimeString(),
            $settings->count(),
        ));

        $totalFetched = 0;
        $totalSynced = 0;

        foreach ($settings as $setting) {
            $sidingId = $setting->siding_id;
            $this->line("  Siding {$sidingId} ({$setting->site_name})");

            try {
                $connector = $tokenManager->getConnector($sidingId);
                $cursor = $from->copy();

                while ($cursor->lt($to)) {
                    $chunkFrom = $cursor->format('Y-m-d H:i:s');
                    $chunkTo = $cursor->copy()->endOfDay()->min($to)->format('Y-m-d H:i:s');
                    $page = 1;

                    do {
                        $response = $connector->send(new GetLoadingEventsRequest(
                            $setting->site_name,
                            $chunkFrom,
                            $chunkTo,
                            $page,
                        ));

                        if (! $response->successful()) {
                            $this->warn("    [{$cursor->toDateString()} p{$page}] HTTP {$response->status()}, skipping.");
                            break;
                        }

                        $body = $response->json() ?? [];
                        $events = $body['data'] ?? [];
                        $totalPages = $body['metaData']['numberOfPages'] ?? 1;

                        if (empty($events)) {
                            break;
                        }

                        $totalFetched += count($events);
                        $this->line(sprintf(
                            '    [%s p%d/%d] %d event(s) fetched',
                            $cursor->toDateString(),
                            $page,
                            $totalPages,
                            count($events),
                        ));

                        if (! $dryRun) {
                            foreach ($events as $event) {
                                if ($this->syncEvent($event, $sidingId)) {
                                    $totalSynced++;
                                }
                            }
                        }

                        $page = $page < $totalPages ? $page + 1 : 0;
                    } while ($page > 0);

                    $cursor = $cursor->addDay();
                }
            } catch (Throwable $e) {
                $this->error("  Siding {$sidingId}: {$e->getMessage()}");
                Log::error('loadrite:backfill error', ['siding_id' => $sidingId, 'error' => $e->getMessage()]);
            }
        }

        $this->info(sprintf(
            '%sDone. Fetched: %d | Synced: %d',
            $dryRun ? '[DRY RUN] ' : '',
            $totalFetched,
            $dryRun ? 0 : $totalSynced,
        ));

        return self::SUCCESS;
    }

    private function syncEvent(array $event, int $sidingId): bool
    {
        if (! isset($event['Sequence'], $event['Weight'])) {
            return false;
        }

        $eventTime = isset($event['Time']) ? Carbon::parse($event['Time']) : null;

        $rake = Rake::query()
            ->where('siding_id', $sidingId)
            ->where('placement_time', '<=', $eventTime ?? now())
            ->where(function ($q) use ($eventTime) {
                $q->whereNull('loading_end_time')
                    ->orWhere('loading_end_time', '>=', $eventTime ?? now());
            })
            ->has('wagonLoadings')
            ->orderByDesc('placement_time')
            ->first();

        // Fallback: if no time-windowed rake found, use the most recent pending/active rake with wagons
        if (! $rake) {
            $rake = Rake::query()
                ->where('siding_id', $sidingId)
                ->whereIn('state', ['loading', 'placed', 'pending'])
                ->has('wagonLoadings')
                ->latest('id')
                ->first();
        }

        if (! $rake) {
            Log::debug('loadrite:backfill — no rake for timestamp', [
                'siding_id' => $sidingId,
                'timestamp' => $event['Time'] ?? null,
                'sequence' => $event['Sequence'] ?? null,
            ]);

            return false;
        }

        $wagonLoading = WagonLoading::query()
            ->where('rake_id', $rake->id)
            ->whereHas('wagon', fn ($q) => $q->where('wagon_sequence', (int) $event['Sequence']))
            ->first();

        if (! $wagonLoading) {
            return false;
        }

        if ($wagonLoading->weight_source === 'weighbridge') {
            return false;
        }

        $updates = [
            'loadrite_weight_mt' => $event['Weight'],
            'loadrite_last_synced_at' => now(),
        ];

        if (! $wagonLoading->loadrite_override) {
            $updates['weight_source'] = 'loadrite';
            $updates['loaded_quantity_mt'] = $event['Weight'];
        }

        $wagonLoading->update($updates);

        $refreshed = $wagonLoading->fresh();

        WagonWeightUpdated::dispatch(
            sidingId: $sidingId,
            wagonId: $wagonLoading->wagon_id,
            sequence: $event['Sequence'],
            loadriteWeightMt: (float) $event['Weight'],
            weightSource: $refreshed->weight_source,
            percentage: $wagonLoading->cc_capacity_mt > 0
                ? round(((float) $event['Weight'] / (float) $wagonLoading->cc_capacity_mt) * 100, 1)
                : 0.0,
            status: $refreshed->weight_source === 'weighbridge' ? 'loaded' : 'loading',
        );

        return true;
    }
}
