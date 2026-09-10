<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RrCharge;
use App\Models\RrDocument;
use App\Services\Railway\Contracts\RrPdfTextExtractorContract;
use App\Services\Railway\RrParserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Re-parses stored RR PDFs and inserts any rr_charges rows missing since the May 2026 parser
 * regression dropped penalty charge codes (POL2, DCLA, FAUC, ENHC, FAOC, POL). Insert-only,
 * never updates or deletes existing rr_charges rows.
 */
final class RrReparseChargesCommand extends Command
{
    protected $signature = 'rr:reparse-charges
                            {--dry-run : Report what would be inserted without writing to DB}
                            {--from= : Only RR documents with rr_received_date on/after this date (Y-m-d)}
                            {--to= : Only RR documents with rr_received_date on/before this date (Y-m-d)}
                            {--id=* : Only these rr_document ids (repeatable)}';

    protected $description = 'Re-parse stored RR PDFs and insert rr_charges rows missing due to the May 2026 parser regression.';

    public function handle(RrParserService $parser, RrPdfTextExtractorContract $extractor): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $docsScanned = 0;
        $docsChanged = 0;
        $rowsAdded = 0;
        $totalByCode = [];

        $query = RrDocument::query()
            ->whereHas('media', fn ($q) => $q->where('collection_name', 'rr_pdf'))
            ->with('media');

        $ids = array_map('intval', $this->option('id'));
        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }
        if ($from = $this->option('from')) {
            $query->whereDate('rr_received_date', '>=', $from);
        }
        if ($to = $this->option('to')) {
            $query->whereDate('rr_received_date', '<=', $to);
        }

        $query->chunkById(100, function ($rrDocuments) use ($parser, $extractor, $dryRun, &$docsScanned, &$docsChanged, &$rowsAdded, &$totalByCode): void {
            foreach ($rrDocuments as $rrDocument) {
                $docsScanned++;

                $media = $rrDocument->getFirstMedia('rr_pdf');
                $path = $media?->getPath();

                if ($path === null || ! is_file($path)) {
                    $this->warn("  doc {$rrDocument->id} ({$rrDocument->rr_number}): PDF missing on disk, skipped");

                    continue;
                }

                try {
                    $text = $extractor->extract($path);
                    $parsed = $parser->parseExtractedText($text);
                } catch (Throwable $e) {
                    $this->warn("  doc {$rrDocument->id} ({$rrDocument->rr_number}): parse failed — {$e->getMessage()}");
                    Log::warning('rr:reparse-charges parse failed', ['rr_document_id' => $rrDocument->id, 'error' => $e->getMessage()]);

                    continue;
                }

                $existingCodes = RrCharge::query()
                    ->where('rr_document_id', $rrDocument->id)
                    ->pluck('charge_code')
                    ->map(fn (string $c): string => mb_strtoupper(mb_trim($c)))
                    ->all();

                $toInsert = [];
                foreach ($parsed['charges'] ?? [] as $c) {
                    $code = (string) ($c['code'] ?? $c['charge_code'] ?? '');
                    if ($code === '' || in_array(mb_strtoupper(mb_trim($code)), $existingCodes, true)) {
                        continue;
                    }
                    $toInsert[] = [
                        'code' => $code,
                        'name' => $c['name'] ?? $c['charge_name'] ?? null,
                        'amount' => (float) ($c['amount'] ?? 0),
                    ];
                }

                if ($toInsert === []) {
                    continue;
                }

                $docsChanged++;
                $codesSummary = implode(', ', array_map(
                    fn (array $c): string => "{$c['code']}={$c['amount']}",
                    $toInsert
                ));
                $this->line(sprintf(
                    '%sdoc %d (%s): +%d charge(s) — %s',
                    $dryRun ? '[DRY RUN] ' : '',
                    $rrDocument->id,
                    $rrDocument->rr_number,
                    count($toInsert),
                    $codesSummary,
                ));

                foreach ($toInsert as $c) {
                    $rowsAdded++;
                    $totalByCode[$c['code']] = ($totalByCode[$c['code']] ?? 0.0) + $c['amount'];
                }

                if (! $dryRun) {
                    DB::transaction(function () use ($rrDocument, $toInsert): void {
                        foreach ($toInsert as $c) {
                            RrCharge::query()->create([
                                'rr_document_id' => $rrDocument->id,
                                'rake_charge_id' => null,
                                'charge_code' => $c['code'],
                                'charge_name' => $c['name'],
                                'amount' => $c['amount'],
                            ]);
                        }
                    });
                }
            }
        });

        $this->newLine();
        $this->info(sprintf(
            '%sDocs scanned: %d | Docs changed: %d | Rows added: %d',
            $dryRun ? '[DRY RUN] ' : '',
            $docsScanned,
            $docsChanged,
            $rowsAdded,
        ));
        foreach ($totalByCode as $code => $amount) {
            $this->line("  {$code}: ".number_format($amount, 2));
        }

        return self::SUCCESS;
    }
}
