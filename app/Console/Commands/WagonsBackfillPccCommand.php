<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Align wagons.pcc_weight_mt (and tare_weight_mt) to the railway's official
 * "CC WEIGHT" table (config/loadrite.php → wagon_cc), keyed by wagon type.
 *
 * The fleet's stored pcc_weight_mt had errors (e.g. 79 MT for BOXNS where the
 * official CC is 70.70). This command makes the official table the single
 * source of truth: every wagon with a known type gets the official CC + tare.
 *
 * Wagons with no wagon_type are left untouched — their type and capacity are
 * filled later from the Loadrite event UserData (loadrite:rebuild-wagon-
 * identities / live ingestion).
 *
 * Scheduled hourly. Always run with --dry-run first.
 */
final class WagonsBackfillPccCommand extends Command
{
    protected $signature = 'wagons:backfill-pcc
                            {--siding= : Limit to one siding id}
                            {--rake= : Limit to one rake id}
                            {--dry-run : Show the plan without writing}';

    protected $description = 'Align wagon pcc_weight_mt + tare to the official CC table by wagon type.';

    public function handle(): int
    {
        $sidingFilter = $this->option('siding') ? (int) $this->option('siding') : null;
        $rakeFilter = $this->option('rake') ? (int) $this->option('rake') : null;
        $dryRun = (bool) $this->option('dry-run');

        // Case-insensitive official CC lookup.
        $lookup = [];
        foreach ((array) config('loadrite.wagon_cc', []) as $type => $cap) {
            $lookup[mb_strtoupper((string) $type)] = $cap;
        }

        if ($lookup === []) {
            $this->error('config/loadrite.php wagon_cc table is empty.');

            return self::FAILURE;
        }

        $targets = DB::table('wagons')
            ->whereNotNull('wagon_type')
            ->where('wagon_type', '!=', '')
            ->when($rakeFilter, fn ($q) => $q->where('rake_id', $rakeFilter))
            ->when($sidingFilter, fn ($q) => $q->whereIn('rake_id', function ($sub) use ($sidingFilter) {
                $sub->select('id')->from('rakes')->where('siding_id', $sidingFilter);
            }))
            ->select('id', 'wagon_type', 'pcc_weight_mt', 'tare_weight_mt')
            ->get();

        $corrected = 0;
        $alreadyOk = 0;
        $unknownType = 0;
        $updates = [];

        foreach ($targets as $w) {
            $cap = $lookup[mb_strtoupper((string) $w->wagon_type)] ?? null;
            if ($cap === null) {
                $unknownType++;

                continue;
            }

            $ccMatches = $w->pcc_weight_mt !== null
                && abs((float) $w->pcc_weight_mt - (float) $cap['cc']) < 0.01;
            $tareMatches = $w->tare_weight_mt !== null
                && abs((float) $w->tare_weight_mt - (float) $cap['tare']) < 0.01;

            if ($ccMatches && $tareMatches) {
                $alreadyOk++;

                continue;
            }

            $updates[] = ['id' => (int) $w->id, 'cc' => (float) $cap['cc'], 'tare' => (float) $cap['tare']];
            $corrected++;
        }

        $this->table(
            ['Result', 'Count'],
            [
                ['wagons with known type', $targets->count() - $unknownType],
                ['already correct', $alreadyOk],
                ['to correct', $corrected],
                ['unknown wagon_type (skipped)', $unknownType],
            ],
        );

        if (! $dryRun && $updates !== []) {
            $written = 0;
            foreach (array_chunk($updates, 500) as $chunk) {
                DB::transaction(function () use ($chunk, &$written): void {
                    foreach ($chunk as $u) {
                        $written += DB::table('wagons')
                            ->where('id', $u['id'])
                            ->update([
                                'pcc_weight_mt' => $u['cc'],
                                'tare_weight_mt' => $u['tare'],
                                'updated_at' => now(),
                            ]);
                    }
                });
            }
            $this->info("Corrected {$written} wagons to the official CC table.");
        } elseif ($dryRun) {
            $this->warn('Dry run — no rows written.');
        } else {
            $this->info('All wagons already match the official CC table.');
        }

        return self::SUCCESS;
    }
}
