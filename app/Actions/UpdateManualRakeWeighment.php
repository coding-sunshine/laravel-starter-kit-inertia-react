<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Models\RakeWeighment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateManualRakeWeighment
{
    public function __construct(private UpdateStockLedger $updateStockLedger) {}

    /**
     * @param  array{total_net_weight_mt: float|string, from_station: string|null, to_station: string|null, priority_number: string|null}  $data
     */
    public function handle(Rake $rake, RakeWeighment $weighment, array $data, int $userId): RakeWeighment
    {
        return DB::transaction(function () use ($rake, $weighment, $data, $userId): RakeWeighment {
            $rake->loadMissing('siding');

            $weighment = RakeWeighment::query()
                ->whereKey($weighment->id)
                ->where('rake_id', $rake->id)
                ->lockForUpdate()
                ->withCount('rakeWagonWeighments')
                ->firstOrFail();

            if ($weighment->rake_wagon_weighments_count > 0) {
                throw new InvalidArgumentException(
                    'This weighment cannot be edited because wagon lines are present. Delete and re-import if needed.',
                );
            }

            $oldNet = $weighment->total_net_weight_mt !== null
                ? round((float) $weighment->total_net_weight_mt, 2)
                : 0.0;

            $newNet = round((float) $data['total_net_weight_mt'], 2);

            if ($newNet <= 0) {
                throw new InvalidArgumentException('Total net weight must be greater than zero.');
            }

            $deltaMt = round($newNet - $oldNet, 2);

            $weighment->update([
                'from_station' => $data['from_station'] ?? null,
                'to_station' => $data['to_station'] ?? null,
                'priority_number' => $data['priority_number'] ?? null,
                'total_net_weight_mt' => $newNet,
            ]);

            $rake->update([
                'loaded_weight_mt' => $newNet,
            ]);

            $siding = $rake->siding;
            if ($siding !== null && abs($deltaMt) >= 0.005) {
                $this->updateStockLedger->applyRakeWeighmentNetDelta(
                    $siding,
                    (int) $rake->id,
                    (int) $weighment->id,
                    $deltaMt,
                    $userId,
                    'Manual rake weighment updated',
                );
            }

            return $weighment->fresh();
        });
    }
}
