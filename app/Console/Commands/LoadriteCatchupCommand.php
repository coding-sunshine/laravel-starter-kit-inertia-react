<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SyncLoadriteEvent;
use App\Http\Integrations\Loadrite\Requests\GetLoadingEventsRequest;
use App\Models\LoadriteSetting;
use App\Services\LoadriteTokenManager;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Force-fetch a Loadrite time window for a siding and ingest every event the
 * API returns, bypassing the scheduled cursor. Used to back-fill events the
 * scheduled poller missed (e.g., events that became API-visible after the
 * cursor had already advanced past their timestamp).
 *
 *     php artisan loadrite:catchup --siding=2 --from="2026-05-16 00:00:00"
 *     php artisan loadrite:catchup --siding=2 --from=today --to=now
 */
final class LoadriteCatchupCommand extends Command
{
    protected $signature = 'loadrite:catchup
                            {--siding= : Siding id (required)}
                            {--from= : Window start, YYYY-MM-DD HH:MM:SS or "today" or "yesterday"}
                            {--to= : Window end, default now}
                            {--dry-run : Show what would be fetched without ingesting}';

    protected $description = 'Catch up missed Loadrite events for a siding by re-polling a time window without using the cursor.';

    public function handle(LoadriteTokenManager $tokenManager, SyncLoadriteEvent $sync): int
    {
        $sidingId = (int) $this->option('siding');
        if ($sidingId === 0) {
            $this->error('--siding=<id> is required');

            return self::FAILURE;
        }

        $from = $this->parseTime($this->option('from') ?? 'today');
        $to = $this->parseTime($this->option('to') ?? 'now');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("siding={$sidingId} from={$from} to={$to}");

        $setting = LoadriteSetting::query()
            ->where('siding_id', $sidingId)
            ->firstOrFail();
        $connector = $tokenManager->getConnector($sidingId);

        $page = 1;
        $maxPages = 200;
        $total = 0;
        $byType = [];
        $newlyInserted = 0;

        do {
            $response = $connector->send(new GetLoadingEventsRequest(
                $setting->site_name,
                $from,
                $to,
                $page,
            ));

            if (! $response->successful()) {
                $this->warn("page={$page} status=".$response->status().' — aborting');
                break;
            }

            $body = $response->json() ?? [];
            $events = $body['data'] ?? [];
            $totalPages = (int) ($body['metaData']['numberOfPages'] ?? 1);

            if ($events === []) {
                break;
            }

            foreach ($events as $event) {
                $total++;
                $type = $event['Event'] ?? 'unknown';
                $byType[$type] = ($byType[$type] ?? 0) + 1;

                if (! $dryRun) {
                    if ($sync->handle($event, $sidingId)) {
                        $newlyInserted++;
                    }
                }
            }

            $this->line("  page {$page}/{$totalPages} → ".count($events).' events');
            $page++;
        } while ($page <= $totalPages && $page <= $maxPages);

        $this->table(
            ['Event', 'Count'],
            collect($byType)->sortKeys()->map(fn ($c, $t) => [$t, $c])->values()->all(),
        );
        $this->info("Total events seen: {$total}");
        if (! $dryRun) {
            $this->info("Newly inserted (idempotent on event_id): {$newlyInserted}");
        } else {
            $this->warn('Dry run — nothing written.');
        }

        return self::SUCCESS;
    }

    private function parseTime(string $value): string
    {
        $value = mb_trim($value);
        $now = CarbonImmutable::now();

        return match ($value) {
            'now' => $now->format('Y-m-d H:i:s'),
            'today' => $now->startOfDay()->format('Y-m-d H:i:s'),
            'yesterday' => $now->subDay()->startOfDay()->format('Y-m-d H:i:s'),
            default => CarbonImmutable::parse($value)->format('Y-m-d H:i:s'),
        };
    }
}
