<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Models\Txr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class StartTxrAction
{
    /**
     * Start a TXR (Train Examination Report) for the given rake.
     * Creates a single TXR record with status in_progress.
     *
     * @return Txr the created TXR
     *
     * @throws InvalidArgumentException if TXR already exists for this rake
     */
    public function handle(Rake $rake, int $userId): Txr
    {
        return DB::transaction(function () use ($rake, $userId): Txr {
            if ($rake->txr !== null) {
                throw new InvalidArgumentException('TXR has already been started for this rake.');
            }

            return $rake->txr()->create([
                'inspection_time' => now(),
                'status' => 'in_progress',
                'created_by' => $userId,
            ]);
        });
    }
}
