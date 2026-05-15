<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill wagons.pcc_weight_mt where it is null/zero.
 *
 * pcc_weight_mt (per-wagon permissible carrying capacity) is the target
 * weight WagonStatusResolver uses to decide Loaded / Overload / Underload.
 * When it is null every loaded wagon falls through to "loading" forever and
 * the loading-progress donut reads 0%.
 *
 * Resolution strategy — purely data-driven, no hardcoded capacities:
 *  1. Wagon HAS a wagon_type → copy the count-weighted modal pcc of every
 *     already-populated wagon of that exact wagon_type.
 *  2. Wagon has NULL wagon_type but its rake has a rake_type → copy the
 *     count-weighted modal pcc of every populated wagon whose wagon_type
 *     begins with that rake_type (the fleet uses fine-grained subtypes,
 *     e.g. rake_type "BOXN" → BOXNHL / BOXNHL25T / ...).
 *  3. Otherwise the wagon is left untouched and reported as unresolved.
 *
 * Always run with --dry-run first.
 */
final class WagonsBackfillPccCommand extends Command
{
    protected $signature = 'wagons:backfill-pcc
                            {--dry-run : Show the plan without writing}
                            {--rake= : Limit to a single rake id}';

    protected $description = 'Backfill wagons.pcc_weight_mt from fleet data where it is null/zero.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $rakeFilter = $this->option('rake') ? (int) $this->option('rake') : null;

        $exactPcc = $this->modalPccByExactType();
        $familyPcc = $this->modalPccByFamily();

        $this->info(sprintf(
            'Reference: %d exact wagon_type capacities, %d rake-type family capacities.',
            count($exactPcc),
            count($familyPcc),
        ));

        $targets = DB::table('wagons')
            ->leftJoin('rakes', 'rakes.id', '=', 'wagons.rake_id')
            ->where(function ($q): void {
                $q->whereNull('wagons.pcc_weight_mt')
                    ->orWhereRaw('wagons.pcc_weight_mt::numeric = 0');
            })
            ->when($rakeFilter, fn ($q) => $q->where('wagons.rake_id', $rakeFilter))
            ->select(
                'wagons.id',
                'wagons.wagon_type',
                'rakes.rake_type',
            )
            ->get();

        $byExact = 0;
        $byFamily = 0;
        $unresolved = 0;
        $updates = [];

        foreach ($targets as $w) {
            $pcc = null;
            $via = null;

            if ($w->wagon_type !== null && isset($exactPcc[$w->wagon_type])) {
                $pcc = $exactPcc[$w->wagon_type];
                $via = 'exact:'.$w->wagon_type;
                $byExact++;
            } elseif ($w->rake_type !== null) {
                $match = $this->matchFamily($w->rake_type, $familyPcc);
                if ($match !== null) {
                    $pcc = $match;
                    $via = 'family:'.$w->rake_type;
                    $byFamily++;
                }
            }

            if ($pcc === null) {
                $unresolved++;

                continue;
            }

            $updates[] = ['id' => (int) $w->id, 'pcc' => $pcc, 'via' => $via];
        }

        $this->table(
            ['Resolution', 'Wagons'],
            [
                ['exact wagon_type', $byExact],
                ['rake_type family', $byFamily],
                ['unresolved (skipped)', $unresolved],
                ['total to update', count($updates)],
            ],
        );

        if ($updates !== []) {
            $sample = array_slice($updates, 0, 8);
            $this->line('Sample:');
            foreach ($sample as $u) {
                $this->line(sprintf('  wagon #%d → pcc %.2f (%s)', $u['id'], $u['pcc'], $u['via']));
            }
        }

        if ($dryRun) {
            $this->warn('Dry run — no rows written. Re-run without --dry-run to apply.');

            return self::SUCCESS;
        }

        $written = 0;
        foreach (array_chunk($updates, 500) as $chunk) {
            DB::transaction(function () use ($chunk, &$written): void {
                foreach ($chunk as $u) {
                    $written += DB::table('wagons')
                        ->where('id', $u['id'])
                        ->update([
                            'pcc_weight_mt' => $u['pcc'],
                            'updated_at' => now(),
                        ]);
                }
            });
        }

        $this->info("Updated {$written} wagons.");

        return self::SUCCESS;
    }

    /**
     * Count-weighted modal pcc for each exact wagon_type among populated wagons.
     *
     * @return array<string, float>
     */
    private function modalPccByExactType(): array
    {
        $rows = DB::table('wagons')
            ->whereNotNull('wagon_type')
            ->whereRaw('pcc_weight_mt::numeric > 0')
            ->selectRaw('wagon_type, pcc_weight_mt::numeric AS pcc, COUNT(*) AS n')
            ->groupBy('wagon_type', DB::raw('pcc_weight_mt::numeric'))
            ->orderByDesc('n')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            // First row per type wins (rows are ordered by descending count).
            if (! isset($out[$r->wagon_type])) {
                $out[$r->wagon_type] = (float) $r->pcc;
            }
        }

        return $out;
    }

    /**
     * Count-weighted modal pcc for each rake-type family. A family is every
     * populated wagon whose wagon_type starts with the rake_type string.
     *
     * @return array<string, float>
     */
    private function modalPccByFamily(): array
    {
        $rakeTypes = DB::table('rakes')
            ->whereNotNull('rake_type')
            ->distinct()
            ->pluck('rake_type')
            ->all();

        $out = [];
        foreach ($rakeTypes as $rakeType) {
            $row = DB::table('wagons')
                ->whereRaw('pcc_weight_mt::numeric > 0')
                ->where('wagon_type', 'like', $rakeType.'%')
                ->selectRaw('pcc_weight_mt::numeric AS pcc, COUNT(*) AS n')
                ->groupBy(DB::raw('pcc_weight_mt::numeric'))
                ->orderByDesc('n')
                ->first();

            if ($row !== null) {
                $out[$rakeType] = (float) $row->pcc;
            }
        }

        return $out;
    }

    /**
     * Resolve a rake_type to a family pcc, preferring the longest (most
     * specific) matching family key.
     *
     * @param  array<string, float>  $familyPcc
     */
    private function matchFamily(string $rakeType, array $familyPcc): ?float
    {
        if (isset($familyPcc[$rakeType])) {
            return $familyPcc[$rakeType];
        }

        $best = null;
        $bestLen = 0;
        foreach ($familyPcc as $key => $pcc) {
            if (str_starts_with($rakeType, $key) && mb_strlen($key) > $bestLen) {
                $best = $pcc;
                $bestLen = mb_strlen($key);
            }
        }

        return $best;
    }
}
