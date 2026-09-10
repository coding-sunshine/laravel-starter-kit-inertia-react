<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\RakeWagonWeighment;
use App\Models\Weighment;
use Illuminate\Support\Facades\DB;

final class RecalculateRakeWeighmentOverload
{
    public function handle(Weighment $weighment): void
    {
        DB::transaction(function () use ($weighment): void {
            $sum = 0.0;

            $weighment->rakeWagonWeighments()->each(function (RakeWagonWeighment $row) use (&$sum): void {
                $over = $this->overloadMtFromNetAndCc($row);
                $row->over_load_mt = $over;
                $row->save();
                $sum += $over;
            });

            $weighment->update([
                'total_over_load_mt' => round($sum, 2),
            ]);
        });
    }

    private function overloadMtFromNetAndCc(RakeWagonWeighment $row): float
    {
        $net = $row->net_weight_mt;
        $cc = $row->cc_capacity_mt;

        if ($net === null || $cc === null) {
            return 0.0;
        }

        return round(max(0.0, (float) $net - (float) $cc), 2);
    }
}
