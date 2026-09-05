<?php

declare(strict_types=1);

namespace App\Services\Rakes;

use App\Models\Rake;
use App\Models\RrDocument;
use Exception;
use Illuminate\Support\Facades\DB;

final readonly class RrService
{
    public function createRrDocument(Rake $rake, array $data): RrDocument
    {
        return DB::transaction(function () use ($rake, $data) {
            // Ensure weighment is completed
            if (! $this->hasSuccessfulWeighment($rake)) {
                throw new Exception('Weighment must be completed before creating RR document');
            }

            // Remove any existing RR documents
            $rake->rrDocuments()->delete();

            // Create new RR document
            $rrDocument = RrDocument::create([
                'rake_id' => $rake->id,
                'rr_number' => $data['rr_number'],
                'rr_received_date' => $data['rr_received_date'],
                'rr_weight_mt' => $data['rr_weight_mt'] ?? null,
                'document_status' => 'draft',
            ]);

            // Update rake state
            $this->updateRakeState($rake, 'rr_generated');

            return $rrDocument;
        });
    }

    public function updateRrDocument(Rake $rake, array $data): RrDocument
    {
        $rrDocument = $rake->rrDocuments()->firstOrFail();

        $rrDocument->update($data);

        return $rrDocument;
    }

    public function canCreateRr(Rake $rake): bool
    {
        return $this->hasSuccessfulWeighment($rake) && ! $rake->rrDocuments()->exists();
    }

    public function hasRrDocument(Rake $rake): bool
    {
        return $rake->rrDocuments()->exists();
    }

    public function getRrDocument(Rake $rake): ?RrDocument
    {
        return $rake->rrDocuments()->first();
    }

    public function finalizeRrDocument(Rake $rake): RrDocument
    {
        $rrDocument = $rake->rrDocuments()->firstOrFail();

        $rrDocument->update(['document_status' => 'finalized']);

        // Update rake state to closed
        $this->updateRakeState($rake, 'closed');

        return $rrDocument;
    }

    private function hasSuccessfulWeighment(Rake $rake): bool
    {
        return $rake->rakeWeighments()->where('status', 'success')->exists();
    }

    private function updateRakeState(Rake $rake, string $state): void
    {
        $rake->update(['state' => $state]);
    }
}
