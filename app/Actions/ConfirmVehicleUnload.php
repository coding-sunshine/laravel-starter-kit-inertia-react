<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VehicleUnload;
use Illuminate\Support\Facades\DB;

final readonly class ConfirmVehicleUnload
{
    public function __construct(
        private UpdateStockLedger $updateStockLedger
    ) {}

    public function handle(VehicleUnload $unload, int $userId): VehicleUnload
    {
        return DB::transaction(function () use ($unload, $userId): VehicleUnload {
            $quantity = (float) ($unload->weighment_weight_mt ?? $unload->mine_weight_mt ?? 0);
            if ($quantity <= 0) {
                $quantity = (float) $unload->mine_weight_mt;
            }
            if ($quantity <= 0) {
                $quantity = 0;
            }

            $unload->update([
                'state' => 'completed',
                'unload_end_time' => now(),
                'updated_by' => $userId,
            ]);

            if ($quantity > 0) {
                $this->updateStockLedger->recordReceiptFromUnload(
                    $unload,
                    $quantity,
                    'Receipt confirmed',
                    $userId
                );
            }

            return $unload->refresh();
        });
    }
}
