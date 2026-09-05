<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Models\RrDocument;
use App\Support\RrmcsDeletionRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final readonly class DeleteRrDocumentAction
{
    public function handle(RrDocument $document): void
    {
        $document->loadMissing('rake');

        $rake = $document->rake;

        DB::transaction(function () use ($document, $rake): void {
            $rakeModel = $rake;

            $document->clearMediaCollection('rr_pdf');
            $document->delete();

            if ($rakeModel === null) {
                return;
            }

            $rakeModel->refresh();

            if (
                RrmcsDeletionRules::shouldDeleteRakeAfterRemovingHistoricalRr($rakeModel)
                && ! $rakeModel->rrDocuments()->exists()
            ) {
                $this->purgeWeighmentFilesForRake($rakeModel);
                $rakeModel->forceDelete();
            }
        });
    }

    private function purgeWeighmentFilesForRake(Rake $rake): void
    {
        Weighment::query()
            ->where('rake_id', $rake->id)
            ->get()
            ->each(function (Weighment $weighment): void {
                if ($weighment->pdf_file_path) {
                    Storage::disk('public')->delete($weighment->pdf_file_path);
                }
                $weighment->clearMediaCollection('weighment_slip_pdf');
            });
    }
}
