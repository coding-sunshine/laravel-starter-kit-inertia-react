<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FAOC (freight adjustment overcharge) prints in the RR's "Rebates" column and
 * is subtracted from Total Freight, but earlier imports matched its generated
 * name "Freight Adjustment (Overcharge)" and classified it as FREIGHT, which
 * inflated basic freight. Move the amount out of the FREIGHT aggregate and into
 * a REBATE aggregate.
 */
return new class extends Migration
{
    public function up(): void
    {
        $faocLines = DB::table('rr_charges as rc')
            ->join('rr_documents as d', 'd.id', '=', 'rc.rr_document_id')
            ->whereRaw('UPPER(rc.charge_code) = ?', ['FAOC'])
            ->whereNotNull('d.rake_id')
            ->select([
                'rc.id as rr_charge_id',
                'rc.amount',
                'd.rake_id',
                'd.diverrt_destination_id',
            ])
            ->get();

        foreach ($faocLines as $line) {
            $amount = round((float) $line->amount, 2);

            if ($amount <= 0) {
                continue;
            }

            $scope = [
                'rake_id' => $line->rake_id,
                'diverrt_destination_id' => $line->diverrt_destination_id,
                'is_actual_charges' => true,
            ];

            // `where(['diverrt_destination_id' => null])` would emit `= null`,
            // which never matches, so scope the column explicitly.
            $scoped = fn (string $chargeType) => DB::table('rake_charges')
                ->where('rake_id', $line->rake_id)
                ->where('is_actual_charges', true)
                ->where('charge_type', $chargeType)
                ->when(
                    $line->diverrt_destination_id === null,
                    fn ($q) => $q->whereNull('diverrt_destination_id'),
                    fn ($q) => $q->where('diverrt_destination_id', $line->diverrt_destination_id),
                );

            $scoped('FREIGHT')->decrement('amount', $amount);

            $rebateId = $scoped('REBATE')->value('id');

            if ($rebateId === null) {
                $rebateId = DB::table('rake_charges')->insertGetId([
                    ...$scope,
                    'charge_type' => 'REBATE',
                    'amount' => $amount,
                    'data_source' => 'rr_import',
                    'remarks' => 'FAOC rebate',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('rake_charges')->where('id', $rebateId)->increment('amount', $amount);
            }

            DB::table('rr_charges')
                ->where('id', $line->rr_charge_id)
                ->update(['rake_charge_id' => $rebateId]);
        }
    }

    public function down(): void
    {
        // One-way data correction: once FAOC is folded back into the FREIGHT
        // total it cannot be told apart from basic freight again.
    }
};
