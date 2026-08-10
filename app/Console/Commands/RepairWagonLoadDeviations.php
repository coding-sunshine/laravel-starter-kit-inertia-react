<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ApplyWeighmentPenaltiesAction;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use Illuminate\Console\Command;

/**
 * Repairs `rake_wagon_weighments.over_load_mt` / `under_load_mt` rows whose
 * stored value disagrees with the weighbridge numbers on the same row —
 * imports produced decimal-shifted values (99.00 MT overload against net 65.99
 * and CC 65.00), which multiplied POL1 penalties by ~100x.
 */
final class RepairWagonLoadDeviations extends Command
{
    protected $signature = 'weighments:repair-load-deviations
                            {--dry-run : Report what would change, write nothing}
                            {--tolerance=0.05 : MT difference above which a stored value is treated as wrong}
                            {--min-cc= : Skip rows whose CC capacity is below this (defaults to the model floor)}
                            {--recalculate : Re-apply weighment penalties for the affected rakes}';

    protected $description = 'Recompute wagon over/under load from net weight and CC capacity where the stored values disagree.';

    public function handle(ApplyWeighmentPenaltiesAction $applyPenalties): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $tolerance = (float) $this->option('tolerance');
        $minCc = $this->option('min-cc') !== null
            ? (float) $this->option('min-cc')
            : RakeWagonWeighment::MIN_PLAUSIBLE_CC_MT;

        $repaired = 0;
        $affectedWeighmentIds = [];

        RakeWagonWeighment::query()
            ->whereNotNull('net_weight_mt')
            ->whereNotNull('cc_capacity_mt')
            ->where('cc_capacity_mt', '>=', $minCc)
            ->chunkById(500, function ($rows) use (&$repaired, &$affectedWeighmentIds, $isDryRun, $tolerance): void {
                foreach ($rows as $row) {
                    $net = (float) $row->net_weight_mt;
                    $capacity = (float) $row->cc_capacity_mt;
                    $expectedOver = round(max(0.0, $net - $capacity), 2);
                    $expectedUnder = round(max(0.0, $capacity - $net), 2);
                    $storedOver = round((float) ($row->over_load_mt ?? 0.0), 2);
                    $storedUnder = round((float) ($row->under_load_mt ?? 0.0), 2);

                    if (abs($storedOver - $expectedOver) <= $tolerance && abs($storedUnder - $expectedUnder) <= $tolerance) {
                        continue;
                    }

                    $repaired++;
                    $affectedWeighmentIds[$row->rake_weighment_id] = true;

                    $this->line(sprintf(
                        'wagon_weighment %d: over %.2f -> %.2f, under %.2f -> %.2f (net %.2f, cc %.2f)',
                        $row->id,
                        $storedOver,
                        $expectedOver,
                        $storedUnder,
                        $expectedUnder,
                        $net,
                        $capacity,
                    ));

                    if (! $isDryRun) {
                        $row->update([
                            'over_load_mt' => $expectedOver,
                            'under_load_mt' => $expectedUnder,
                        ]);
                    }
                }
            });

        $this->info(sprintf('%s %d wagon weighment row(s).', $isDryRun ? 'Would repair' : 'Repaired', $repaired));

        if ($isDryRun || ! $this->option('recalculate') || $affectedWeighmentIds === []) {
            return Command::SUCCESS;
        }

        $weighments = RakeWeighment::query()
            ->with('rake.siding')
            ->whereIn('id', array_keys($affectedWeighmentIds))
            ->get();

        foreach ($weighments as $weighment) {
            if ($weighment->rake === null) {
                continue;
            }

            $applyPenalties->handle($weighment->rake, $weighment);
        }

        $this->info(sprintf('Re-applied weighment penalties for %d weighment(s).', $weighments->count()));

        return Command::SUCCESS;
    }
}
