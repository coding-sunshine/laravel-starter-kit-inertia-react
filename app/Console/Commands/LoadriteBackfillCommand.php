<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SyncLoadriteEvent;
use App\Http\Integrations\Loadrite\Requests\GetLoadingEventsRequest;
use App\Models\LoadriteSetting;
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

    public function handle(LoadriteTokenManager $tokenManager, SyncLoadriteEvent $sync): int
    {
        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))->startOfDay()
            : now()->subDays(7)->startOfDay();

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
                                if ($sync->handle($event, $sidingId)) {
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
            '%sDone. Fetched: %d | New events synced: %d',
            $dryRun ? '[DRY RUN] ' : '',
            $totalFetched,
            $dryRun ? 0 : $totalSynced,
        ));

        return self::SUCCESS;
    }
}
