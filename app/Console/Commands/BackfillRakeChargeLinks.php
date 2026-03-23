<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AppliedPenalty;
use App\Models\RakeCharge;
use App\Models\RrDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class BackfillRakeChargeLinks extends Command
{
    protected $signature = 'rake-charges:backfill-links {--dry-run : Show summary without persisting changes}';

    protected $description = 'Backfill rake_charge_id links for RR charges and penalty tables';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $runner = function (): void {
            $this->backfillRrCharges();
            $this->backfillPenaltySnapshots();
            $this->backfillAppliedPenalties();
        };

        if ($dryRun) {
            DB::beginTransaction();
            $runner();
            DB::rollBack();
            $this->info('Dry run completed. No changes were persisted.');

            return self::SUCCESS;
        }

        DB::transaction($runner);
        $this->info('Backfill completed successfully.');

        return self::SUCCESS;
    }

    private function backfillRrCharges(): void
    {
        $updated = 0;

        RrDocument::query()
            ->with(['rrCharges', 'rake'])
            ->whereHas('rrCharges', fn ($q) => $q->whereNull('rake_charge_id'))
            ->chunkById(100, function ($documents) use (&$updated): void {
                foreach ($documents as $document) {
                    if ($document->rake_id === null) {
                        continue;
                    }

                    $totalsByType = [];
                    foreach ($document->rrCharges as $rrCharge) {
                        $type = $this->resolveChargeType(
                            (string) $rrCharge->charge_code,
                            $rrCharge->charge_name,
                            (float) $rrCharge->amount
                        );
                        $totalsByType[$type] = (float) ($totalsByType[$type] ?? 0.0) + (float) $rrCharge->amount;
                    }

                    $chargeByType = [];
                    foreach ($totalsByType as $type => $amount) {
                        $chargeByType[$type] = RakeCharge::query()->updateOrCreate(
                            [
                                'rake_id' => $document->rake_id,
                                'diverrt_destination_id' => $document->diverrt_destination_id,
                                'charge_type' => $type,
                                'is_actual_charges' => true,
                            ],
                            [
                                'amount' => round($amount, 2),
                                'data_source' => 'rr_backfill',
                                'remarks' => 'Backfilled from rr_charges',
                            ]
                        );
                    }

                    foreach ($document->rrCharges as $rrCharge) {
                        if ($rrCharge->rake_charge_id !== null) {
                            continue;
                        }
                        $type = $this->resolveChargeType(
                            (string) $rrCharge->charge_code,
                            $rrCharge->charge_name,
                            (float) $rrCharge->amount
                        );
                        $linked = $chargeByType[$type] ?? null;
                        if ($linked === null) {
                            continue;
                        }
                        $rrCharge->update(['rake_charge_id' => $linked->id]);
                        $updated++;
                    }
                }
            });

        $this->line("Linked rr_charges rows: {$updated}");
    }

    private function backfillPenaltySnapshots(): void
    {
        $updated = 0;

        $groups = DB::table('rr_penalty_snapshots as ps')
            ->leftJoin('rr_documents as rd', 'rd.id', '=', 'ps.rr_document_id')
            ->whereNull('ps.rake_charge_id')
            ->whereNotNull('ps.rake_id')
            ->selectRaw('ps.rr_document_id, ps.rake_id, rd.diverrt_destination_id, SUM(ps.amount) as total_amount')
            ->groupBy('ps.rr_document_id', 'ps.rake_id', 'rd.diverrt_destination_id')
            ->get();

        foreach ($groups as $group) {
            $penaltyCharge = RakeCharge::query()->updateOrCreate(
                [
                    'rake_id' => (int) $group->rake_id,
                    'diverrt_destination_id' => $group->diverrt_destination_id !== null ? (int) $group->diverrt_destination_id : null,
                    'charge_type' => 'PENALTY',
                    'is_actual_charges' => true,
                ],
                [
                    'amount' => round((float) $group->total_amount, 2),
                    'data_source' => 'rr_backfill',
                    'remarks' => 'Backfilled from rr_penalty_snapshots',
                ]
            );

            $count = DB::table('rr_penalty_snapshots')
                ->where('rr_document_id', (int) $group->rr_document_id)
                ->where('rake_id', (int) $group->rake_id)
                ->whereNull('rake_charge_id')
                ->update(['rake_charge_id' => $penaltyCharge->id]);
            $updated += $count;
        }

        $this->line("Linked rr_penalty_snapshots rows: {$updated}");
    }

    private function backfillAppliedPenalties(): void
    {
        $updated = 0;

        $groups = AppliedPenalty::query()
            ->whereNull('rake_charge_id')
            ->selectRaw('rake_id, SUM(amount) as total_amount')
            ->groupBy('rake_id')
            ->get();

        foreach ($groups as $group) {
            $penaltyCharge = RakeCharge::query()->updateOrCreate(
                [
                    'rake_id' => (int) $group->rake_id,
                    'diverrt_destination_id' => null,
                    'charge_type' => 'PENALTY',
                    'is_actual_charges' => false,
                ],
                [
                    'amount' => round((float) $group->total_amount, 2),
                    'data_source' => 'predicted_penalty_backfill',
                    'remarks' => 'Backfilled from applied_penalties',
                ]
            );

            $count = AppliedPenalty::query()
                ->where('rake_id', (int) $group->rake_id)
                ->whereNull('rake_charge_id')
                ->update(['rake_charge_id' => $penaltyCharge->id]);
            $updated += $count;
        }

        $this->line("Linked applied_penalties rows: {$updated}");
    }

    private function resolveChargeType(string $code, ?string $name, float $amount): string
    {
        $normalizedCode = mb_strtoupper(mb_trim($code));
        $normalizedName = mb_strtoupper(mb_trim((string) $name));

        if (in_array($normalizedCode, ['POL1', 'POLA', 'DEM'], true)) {
            return 'PENALTY';
        }

        if (str_contains($normalizedCode, 'GST') || str_contains($normalizedName, 'GST')) {
            return 'GST';
        }

        if (
            $normalizedCode === 'FREIGHT'
            || str_contains($normalizedCode, 'FRT')
            || str_contains($normalizedName, 'FREIGHT')
        ) {
            return 'FREIGHT';
        }

        if ($normalizedCode === 'REBATE' || $amount < 0) {
            return 'REBATE';
        }

        return 'OTHER_CHARGE';
    }
}
