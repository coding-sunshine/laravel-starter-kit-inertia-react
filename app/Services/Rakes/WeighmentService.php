<?php

declare(strict_types=1);

namespace App\Services\Rakes;

use App\Models\Rake;
use App\Models\RakeWeighment;
use App\Models\Wagon;
use App\Models\WagonWeight;
use App\Models\Weighment;
use Exception;
use Illuminate\Support\Facades\DB;

final readonly class WeighmentService
{
    public function recordWeighment(Rake $rake, array $data): Weighment
    {
        return DB::transaction(function () use ($rake, $data) {
            // Ensure guard inspection is approved
            if (! $this->isGuardApproved($rake)) {
                throw new Exception('Guard must approve before weighment');
            }

            // Get latest attempt number
            $latestAttempt = $rake->rakeWeighments()->max('attempt_no') ?? 0;
            $attemptNo = $latestAttempt + 1;

            // Store PDF if provided
            $pdfPath = null;
            if (isset($data['weighment_pdf']) && $data['weighment_pdf'] instanceof \Illuminate\Http\UploadedFile) {
                $pdfPath = $data['weighment_pdf']->store('weighment-pdfs', 'public');
            }

            // Create weighment record
            $weighment = Weighment::create([
                'rake_id' => $rake->id,
                'weighment_time' => now(),
                'train_speed_kmph' => $data['train_speed_kmph'],
                'attempt_no' => $attemptNo,
                'pdf_path' => $pdfPath,
                'status' => 'processing',
            ]);

            // Process PDF and extract weights (mock implementation)
            try {
                $this->processWeighmentPdf($weighment, $data);
                $weighment->update(['status' => 'success']);
                $this->updateRakeState($rake, 'weighment_completed');
            } catch (Exception $e) {
                $weighment->update(['status' => 'failed']);
                throw $e;
            }

            return $weighment;
        });
    }

    public function canWeigh(Rake $rake): bool
    {
        return $this->isGuardApproved($rake) && ! $this->hasSuccessfulWeighment($rake);
    }

    public function hasSuccessfulWeighment(Rake $rake): bool
    {
        return $rake->rakeWeighments()->where('status', 'success')->exists();
    }

    public function getLatestWeighment(Rake $rake): ?RakeWeighment
    {
        return $rake->rakeWeighments()->latest()->first();
    }

    private function processWeighmentPdf(Weighment $weighment, array $data): void
    {
        // Mock implementation - in real scenario, this would parse PDF
        $rake = $weighment->rake;
        $totalWeight = 0;

        // Create wagon weight records (mock data)
        foreach ($rake->wagons as $wagon) {
            $grossWeight = rand(50000, 65000) / 1000; // Random between 50-65 MT
            $netWeight = $grossWeight - rand(15000, 25000) / 1000; // Subtract tare weight

            WagonWeight::create([
                'weighment_id' => $weighment->id,
                'wagon_id' => $wagon->id,
                'gross_weight_mt' => number_format($grossWeight, 2),
                'net_weight_mt' => number_format($netWeight, 2),
            ]);

            $totalWeight += $netWeight;
        }

        // Update total weight
        $weighment->update(['total_weight_mt' => number_format($totalWeight, 2)]);
    }

    private function isGuardApproved(Rake $rake): bool
    {
        $inspection = $rake->guardInspections()->first();

        return $inspection?->is_approved ?? false;
    }

    private function updateRakeState(Rake $rake, string $state): void
    {
        $rake->update(['state' => $state]);
    }
}
