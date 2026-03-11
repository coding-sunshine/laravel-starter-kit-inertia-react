<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Models\Txr;
use Illuminate\Support\Facades\DB;

final readonly class UpdateTxrHeaderAction
{
    /**
     * Create or update the TXR header (inspection times, status, remarks).
     * If no TXR exists, creates one; otherwise updates.
     *
     * @param  array{inspection_time: string, inspection_end_time?: string|null, status: string, remarks?: string|null}  $data
     * @return Txr the created or updated TXR
     */
    public function handle(Rake $rake, array $data, int $userId): Txr
    {
        return DB::transaction(function () use ($rake, $data, $userId): Txr {
            $txr = $rake->txr;
            $payload = [
                'inspection_time' => $data['inspection_time'],
                'inspection_end_time' => $data['inspection_end_time'] ?? null,
                'status' => $data['status'],
                'remarks' => $data['remarks'] ?? null,
            ];

            if ($txr === null) {
                return $rake->txr()->create([
                    ...$payload,
                    'created_by' => $userId,
                ]);
            }

            $txr->update([
                ...$payload,
                'updated_by' => $userId,
            ]);

            return $txr->fresh();
        });
    }
}
