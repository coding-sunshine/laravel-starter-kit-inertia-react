<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Loadrite\LoadriteUserDataParser;
use App\Services\Loadrite\WagonCapacityResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill real wagon identity onto wagons that were created as W1..W59
 * placeholders, using the rake/wagon/type values operators keyed into the
 * Loadrite event UserData fields.
 *
 * For every Short Total event already attributed to a wagon (wagon_id set),
 * the event's raw_payload is parsed and the wagon row is updated with the real
 * wagon number, wagon type and carrying capacity.
 *
 * Idempotent. Run with --dry-run first.
 */
final class LoadriteRebuildWagonIdentitiesCommand extends Command
{
    protected $signature = 'loadrite:rebuild-wagon-identities
                            {--siding= : Limit to one siding id}
                            {--dry-run : Show the plan without writing}';

    protected $description = 'Backfill real wagon number / type / CC onto placeholder wagons from Loadrite event UserData.';

    public function handle(LoadriteUserDataParser $parser, WagonCapacityResolver $capacity): int
    {
        $sidingFilter = $this->option('siding') ? (int) $this->option('siding') : null;
        $dryRun = (bool) $this->option('dry-run');

        $events = DB::table('loadrite_events')
            ->where('event_type', 'Short Total')
            ->whereNotNull('wagon_id')
            ->when($sidingFilter, fn ($q) => $q->where('siding_id', $sidingFilter))
            ->orderBy('event_time')
            ->orderBy('id')
            ->select('wagon_id', 'raw_payload')
            ->get();

        $this->info("Processing {$events->count()} attributed Short Total events…");

        $stampedNumber = 0;
        $stampedType = 0;
        $stampedCc = 0;
        $unresolved = 0;
        $touched = [];

        $bar = $this->output->createProgressBar($events->count());
        $bar->start();

        foreach ($events as $e) {
            $bar->advance();

            $payload = json_decode((string) $e->raw_payload, true);
            if (! is_array($payload)) {
                continue;
            }

            $parsed = $parser->parse($payload);
            $cap = $capacity->resolve($parsed['wagon_number'], $parsed['wagon_type']);

            $wagon = DB::table('wagons')->where('id', $e->wagon_id)
                ->first(['id', 'wagon_number', 'wagon_type', 'pcc_weight_mt']);
            if ($wagon === null) {
                continue;
            }

            $update = [];

            $isPlaceholder = $wagon->wagon_number === null
                || preg_match('/^W\d+$/i', (string) $wagon->wagon_number) === 1;
            if ($parsed['wagon_number'] !== null && $isPlaceholder) {
                $update['wagon_number'] = $parsed['wagon_number'];
                $stampedNumber++;
            }

            if (($wagon->wagon_type === null || $wagon->wagon_type === '') && $cap['type'] !== null) {
                $update['wagon_type'] = $cap['type'];
                $stampedType++;
            }

            if ($cap['cc'] !== null && $cap['cc'] > 0) {
                $update['pcc_weight_mt'] = $cap['cc'];
                $stampedCc++;
            }

            if ($cap['tare'] !== null && $cap['tare'] > 0) {
                $update['tare_weight_mt'] = $cap['tare'];
            }

            if ($cap['cc'] === null && $parsed['wagon_number'] === null) {
                $unresolved++;
            }

            if ($update !== [] && ! $dryRun) {
                $update['updated_at'] = now();
                DB::table('wagons')->where('id', $wagon->id)->update($update);
            }
            $touched[$e->wagon_id] = true;
        }

        $bar->finish();
        $this->newLine();

        $this->table(
            ['Result', 'Count'],
            [
                ['wagons touched', count($touched)],
                ['wagon_number stamped', $stampedNumber],
                ['wagon_type stamped', $stampedType],
                ['pcc/CC stamped', $stampedCc],
                ['unresolved (no number, no type)', $unresolved],
            ],
        );

        if ($dryRun) {
            $this->warn('Dry run — no rows written.');
        } else {
            $this->info('Wagon identities rebuilt.');
        }

        return self::SUCCESS;
    }
}
