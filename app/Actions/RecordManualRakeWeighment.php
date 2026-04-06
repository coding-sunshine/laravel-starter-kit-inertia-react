<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Models\RakeWeighment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RecordManualRakeWeighment
{
    public function __construct(private UpdateStockLedger $updateStockLedger) {}

    /**
     * @param  array{total_net_weight_mt: float|string, from_station: string|null, to_station: string|null, priority_number: string|null}  $data
     */
    public function handle(Rake $rake, array $data, int $userId): RakeWeighment
    {
        return DB::transaction(function () use ($rake, $data, $userId): RakeWeighment {
            $rake->loadMissing('siding');

            $existing = $rake->rakeWeighments()
                ->withCount('rakeWagonWeighments')
                ->first();

            if ($existing !== null) {
                if ($existing->rake_wagon_weighments_count > 0) {
                    throw new InvalidArgumentException(
                        'A weighment has already been recorded for this rake.',
                    );
                }

                throw new InvalidArgumentException(
                    'A manual weighment already exists for this rake. Upload the weighment document when it is available.',
                );
            }

            $netMt = round((float) $data['total_net_weight_mt'], 2);

            if ($netMt <= 0) {
                throw new InvalidArgumentException('Total net weight must be greater than zero.');
            }

            $weighment = RakeWeighment::query()->create([
                'rake_id' => $rake->id,
                'attempt_no' => 1,
                'gross_weighment_datetime' => null,
                'tare_weighment_datetime' => null,
                'train_name' => null,
                'direction' => null,
                'commodity' => null,
                'from_station' => $data['from_station'] ?? null,
                'to_station' => $data['to_station'] ?? null,
                'priority_number' => $data['priority_number'] ?? null,
                'total_gross_weight_mt' => null,
                'total_tare_weight_mt' => null,
                'total_net_weight_mt' => $netMt,
                'total_cc_weight_mt' => null,
                'total_under_load_mt' => null,
                'total_over_load_mt' => null,
                'maximum_train_speed_kmph' => null,
                'maximum_weight_mt' => null,
                'pdf_file_path' => null,
                'status' => 'success',
                'created_by' => $userId,
            ]);

            $rake->update([
                'data_source' => 'system',
                'loaded_weight_mt' => $weighment->total_net_weight_mt,
                'under_load_mt' => null,
                'over_load_mt' => null,
            ]);

            $siding = $rake->siding;
            if ($siding !== null) {
                $this->updateStockLedger->recordDispatch(
                    $siding,
                    $netMt,
                    $rake->id,
                    'Manual rake weighment',
                    $userId,
                );
            }

            return $weighment->fresh();
        });
    }
}
