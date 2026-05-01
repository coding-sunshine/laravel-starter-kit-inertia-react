<?php

declare(strict_types=1);

use App\Actions\ApplyDemurragePenaltyAction;
use App\Actions\ApplyPloPenaltyAction;
use App\Actions\ReconcilePenaltyHeadsAction;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use App\Models\RrPenaltySnapshot;
use App\Models\Siding;
use App\Models\Wagon;
use Illuminate\Support\Facades\File;

it('predicted matches billed within ±10% across the calibration corpus', function (): void {
    $dir = base_path('tests/Fixtures/RailwayBills');
    $files = collect(File::files($dir))
        ->filter(fn ($f): bool => str_ends_with($f->getFilename(), '.json'));

    expect($files)->not->toBeEmpty('Calibration corpus is empty — populate before merging Stage 1.');

    $synthetic = $files->filter(function ($f): bool {
        $j = json_decode(File::get($f->getPathname()), true);

        return ($j['synthetic'] ?? false) === true;
    });
    expect($synthetic->count())->toBeLessThan(
        $files->count(),
        'Calibration corpus contains only synthetic samples — add real RR-derived fixtures before merge.'
    );

    $tolerance = 0.10;
    $action = resolve(ReconcilePenaltyHeadsAction::class);

    foreach ($files as $file) {
        $sample = json_decode(File::get($file->getPathname()), true);
        if (($sample['synthetic'] ?? false) === true) {
            continue; // skip synthetic placeholders in calibration assertions; they only count against the all-synthetic gate above
        }

        $rake = buildRakeFromSample($sample);

        $action->handle($rake);

        foreach ($sample['billed'] as $code => $billedAmount) {
            if ($billedAmount === 0) {
                continue;
            }
            $row = PenaltyReconciliation::query()
                ->where('rake_id', $rake->id)
                ->where('penalty_code', $code)
                ->first();

            expect($row)->not->toBeNull("Missing reconciliation row for {$code} on rake {$sample['rake_number']}");

            $predicted = (float) ($row->predicted_amount ?? 0.0);
            $diffRatio = $billedAmount > 0 ? abs($predicted - $billedAmount) / $billedAmount : 0.0;
            expect($diffRatio)->toBeLessThanOrEqual(
                $tolerance,
                "{$code} prediction off by ".round($diffRatio * 100, 2)."% on rake {$sample['rake_number']} (predicted ₹{$predicted}, billed ₹{$billedAmount})"
            );
        }
    }
});

/**
 * Build a Rake (and supporting weighment + billed-snapshot rows) from a calibration sample,
 * then drive the project's existing penalty-applying Actions so the predicted_amount is what
 * the production code path would produce.
 *
 * @param  array<string, mixed>  $sample
 */
function buildRakeFromSample(array $sample): Rake
{
    $siding = Siding::factory()->create(['name' => $sample['siding_name']]);
    $rake = Rake::factory()->for($siding)->create([
        'rake_number' => $sample['rake_number'],
        'commodity_grade' => $sample['commodity_grade'] ?? 'UNGRADED',
        'wagon_count' => $sample['wagon_count'],
        'placement_time' => $sample['placement_time'],
        'loading_end_time' => $sample['loading_end_time'],
    ]);

    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($sample['wagons'] as $w) {
        $wagon = Wagon::factory()->create([
            'wagon_number' => $w['wagon_number'],
        ]);
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $wagon->id,
            'cc_capacity_mt' => $w['cc_capacity_mt'],
            'net_weight_mt' => $w['net_weight_mt'],
        ]);
    }

    foreach ($sample['billed'] as $code => $amount) {
        if ($amount > 0) {
            RrPenaltySnapshot::factory()->for($rake)->create([
                'penalty_code' => $code,
                'amount' => $amount,
            ]);
        }
    }

    resolve(ApplyDemurragePenaltyAction::class)->handle($rake);
    resolve(ApplyPloPenaltyAction::class)->handle($rake, $weighment);

    return $rake;
}
