<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rake;
use App\Models\Txr;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

final readonly class TxrService
{
    public function create(Rake $rake, array $data): Txr
    {
        return DB::transaction(function () use ($rake, $data) {
            // Ensure no duplicate TXR exists for this rake
            $this->ensureNoDuplicateTxr($rake);

            $startTime = Carbon::parse($data['txr_start_time']);
            $endTime = Carbon::parse($data['txr_end_time']);
            $durationMinutes = $startTime->diffInMinutes($endTime);

            $txr = Txr::create([
                'rake_id' => $rake->id,
                'txr_start_time' => $startTime,
                'txr_end_time' => $endTime,
                'duration_minutes' => $durationMinutes,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Update rake state
            $rake->update(['state' => 'txr_completed']);

            return $txr;
        });
    }

    public function update(Rake $rake, array $data): Txr
    {
        return DB::transaction(function () use ($rake, $data) {
            $txr = $rake->txr;
            
            if (!$txr) {
                throw new \InvalidArgumentException('TXR record not found for this rake.');
            }

            $startTime = Carbon::parse($data['txr_start_time']);
            $endTime = Carbon::parse($data['txr_end_time']);
            $durationMinutes = $startTime->diffInMinutes($endTime);

            $txr->update([
                'txr_start_time' => $startTime,
                'txr_end_time' => $endTime,
                'duration_minutes' => $durationMinutes,
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            return $txr->fresh();
        });
    }

    private function ensureNoDuplicateTxr(Rake $rake): void
    {
        if ($rake->txr()->exists()) {
            throw new \InvalidArgumentException('TXR record already exists for this rake.');
        }
    }
}
