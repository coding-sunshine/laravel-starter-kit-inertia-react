<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppliedPenalty;
use App\Models\Weighment;
use App\Support\RrmcsDeletionRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final readonly class DeleteStandaloneHistoricalWeighmentAction
{
    public function handle(Weighment $weighment): void
    {
        $weighment->loadMissing('rake');

        $rake = $weighment->rake;
        if ($rake === null) {
            throw new InvalidArgumentException('Weighment has no associated rake.');
        }

        if (! RrmcsDeletionRules::isStandaloneHistoricalWeighmentRake($rake)) {
            throw new InvalidArgumentException('Only historical weighment imports from the weighments module can be deleted here.');
        }

        DB::transaction(function () use ($rake): void {
            Weighment::query()
                ->where('rake_id', $rake->id)
                ->get()
                ->each(function (Weighment $row): void {
                    if ($row->pdf_file_path) {
                        Storage::disk('public')->delete($row->pdf_file_path);
                    }
                    $row->clearMediaCollection('weighment_slip_pdf');
                });

            AppliedPenalty::query()
                ->where('rake_id', $rake->id)
                ->where('meta->source', 'weighment')
                ->whereHas('penaltyType', function ($query): void {
                    $query->where('code', '!=', 'DEM');
                })
                ->delete();

            $rake->forceDelete();
        });
    }
}
