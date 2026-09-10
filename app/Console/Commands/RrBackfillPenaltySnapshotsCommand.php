<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RrCharge;
use App\Models\RrPenaltySnapshot;
use App\Services\Railway\RrImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfills rr_penalty_snapshots for rr_charges rows carrying a penalty code that never became
 * a snapshot because the import gate didn't recognize the code at the time (May 2026 parser
 * regression). Insert-only, never dispatches notifications/events, never touches applied_penalties.
 */
final class RrBackfillPenaltySnapshotsCommand extends Command
{
    protected $signature = 'rr:backfill-penalty-snapshots
                            {--dry-run : Report what would be inserted without writing to DB}
                            {--from= : Only rr_charges whose RR document has rr_received_date on/after this date (Y-m-d)}
                            {--to= : Only rr_charges whose RR document has rr_received_date on/before this date (Y-m-d)}';

    protected $description = 'Backfill rr_penalty_snapshots from existing rr_charges rows carrying penalty codes.';

    public function handle(RrImportService $importService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $inserted = 0;
        $skippedExisting = 0;
        $totalByCode = [];

        $query = RrCharge::query()
            ->with('rrDocument')
            ->where('amount', '>', 0)
            ->whereHas('rrDocument', function ($q): void {
                if ($from = $this->option('from')) {
                    $q->whereDate('rr_received_date', '>=', $from);
                }
                if ($to = $this->option('to')) {
                    $q->whereDate('rr_received_date', '<=', $to);
                }
            });

        $query->chunkById(200, function ($charges) use ($importService, $dryRun, &$inserted, &$skippedExisting, &$totalByCode): void {
            foreach ($charges as $charge) {
                if (! $importService->isPenaltyCode($charge->charge_code)) {
                    continue;
                }

                $rrDocument = $charge->rrDocument;
                if ($rrDocument === null) {
                    continue;
                }

                $exists = RrPenaltySnapshot::query()
                    ->where('rr_document_id', $rrDocument->id)
                    ->where('penalty_code', $charge->charge_code)
                    ->exists();

                if ($exists) {
                    $skippedExisting++;

                    continue;
                }

                $inserted++;
                $totalByCode[$charge->charge_code] = ($totalByCode[$charge->charge_code] ?? 0.0) + (float) $charge->amount;

                $this->line(sprintf(
                    '%sdoc %d (%s): +%s %s',
                    $dryRun ? '[DRY RUN] ' : '',
                    $rrDocument->id,
                    $rrDocument->rr_number,
                    $charge->charge_code,
                    number_format((float) $charge->amount, 2),
                ));

                if (! $dryRun) {
                    DB::transaction(function () use ($rrDocument, $charge): void {
                        RrPenaltySnapshot::query()->create([
                            'rr_document_id' => $rrDocument->id,
                            'rake_id' => $rrDocument->rake_id,
                            // rake_charge_id intentionally left null: RrImportService's link
                            // (resolveOrCreatePenaltyCharge) recomputes a rake's aggregate PENALTY
                            // RakeCharge amount from a whole charge batch, which isn't a safe
                            // operation to replay per-row for historical backfill.
                            'rake_charge_id' => null,
                            'penalty_code' => $charge->charge_code,
                            'amount' => $charge->amount,
                            'wagon_number' => null,
                            'wagon_sequence' => null,
                            'meta' => ['name' => $charge->charge_name],
                        ]);
                    });
                }
            }
        });

        $this->newLine();
        $this->info(sprintf(
            '%sRows inserted: %d | Rows skipped (existing): %d',
            $dryRun ? '[DRY RUN] ' : '',
            $inserted,
            $skippedExisting,
        ));
        foreach ($totalByCode as $code => $amount) {
            $this->line("  {$code}: ".number_format($amount, 2));
        }

        return self::SUCCESS;
    }
}
