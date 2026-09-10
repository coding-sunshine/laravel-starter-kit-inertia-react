<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Txr;
use App\Models\Wagon;
use App\Models\WagonUnfitLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class WagonUnfitService
{
    public function store(Txr $txr, array $data): void
    {
        DB::transaction(function () use ($txr, $data) {
            $unfitWagons = $data['unfit_wagons'];

            // Get all wagon IDs from the request
            $wagonIds = array_column($unfitWagons, 'wagon_id');

            // Validate all wagons belong to the same rake
            $this->validateWagonsBelongToRake($txr->rake_id, $wagonIds);

            // Prevent duplicates within the request
            $uniqueWagonIds = array_unique($wagonIds);
            if (count($uniqueWagonIds) !== count($wagonIds)) {
                throw new InvalidArgumentException('Duplicate wagon entries detected in the request.');
            }

            // Prevent duplicates with existing records
            $this->preventDuplicates($txr->id, $uniqueWagonIds);

            // Insert unfit wagon logs (use reason_unfit as reason; marked_by is not stored)
            $unfitLogs = [];
            foreach ($unfitWagons as $unfitWagon) {
                $unfitLogs[] = [
                    'txr_id' => $txr->id,
                    'wagon_id' => $unfitWagon['wagon_id'],
                    'reason' => $unfitWagon['reason_unfit'] ?? $unfitWagon['reason'] ?? null,
                    'marking_method' => $unfitWagon['marking_method'] ?? null,
                    'marked_at' => $unfitWagon['marked_at'] ?? now(),
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            WagonUnfitLog::insert($unfitLogs);

            // Update wagons table to mark as unfit
            Wagon::whereIn('id', $uniqueWagonIds)->update(['is_unfit' => true]);
        });
    }

    private function validateWagonsBelongToRake(int $rakeId, array $wagonIds): void
    {
        $wagonCount = Wagon::where('rake_id', $rakeId)
            ->whereIn('id', $wagonIds)
            ->count();

        if ($wagonCount !== count($wagonIds)) {
            throw new InvalidArgumentException('One or more wagons do not belong to this rake.');
        }
    }

    private function preventDuplicates(int $txrId, array $wagonIds): void
    {
        $existingCount = WagonUnfitLog::where('txr_id', $txrId)
            ->whereIn('wagon_id', $wagonIds)
            ->count();

        if ($existingCount > 0) {
            throw new InvalidArgumentException('One or more wagons are already marked as unfit for this TXR.');
        }
    }
}
