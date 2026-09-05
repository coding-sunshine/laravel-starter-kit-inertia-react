# rr:reparse-charges

## Purpose

Console command. Re-parses stored RR PDFs and inserts any `rr_charges` rows missing since the May 2026 parser regression dropped penalty charge codes (`POL2`, `DCLA`, `FAUC`, `ENHC`, `FAOC`, `POL`). Insert-only: never updates or deletes existing `rr_charges` rows.

## Location

`app/Console/Commands/RrReparseChargesCommand.php`

## Signature

```
rr:reparse-charges
    {--dry-run : Report what would be inserted without writing to DB}
    {--from= : Only RR documents with rr_received_date on/after this date (Y-m-d)}
    {--to= : Only RR documents with rr_received_date on/before this date (Y-m-d)}
    {--id=* : Only these rr_document ids (repeatable)}
```

## Method Signature

```php
public function handle(RrParserService $parser, RrPdfTextExtractorContract $extractor): int
```

## Dependencies

- `App\Services\Railway\RrParserService` — parses extracted PDF text into header/charge/wagon data (`parseExtractedText()`)
- `App\Services\Railway\Contracts\RrPdfTextExtractorContract` — `pdftotext -layout` wrapper (`App\Services\Railway\RrPdfTextExtractor`, bound in `AppServiceProvider`)

## Behavior

1. Queries `RrDocument` rows that have `rr_pdf` media, filterable by `--from`/`--to` (on `rr_received_date`) or `--id` (repeatable), chunked by 100.
2. For each document: reads the stored PDF from disk, extracts text via `RrPdfTextExtractorContract::extract()`, parses it via `RrParserService::parseExtractedText()`. Documents with a missing PDF file or a parse failure are skipped with a warning (parse failures also logged via `Log::warning`).
3. Compares parsed charge codes (`code`/`charge_code` key, uppercased/trimmed) against `RrCharge` rows already stored for that document; only charges not already present are queued for insert.
4. Unless `--dry-run`, inserts the missing `RrCharge` rows inside `DB::transaction()`, with `rake_charge_id` left `null`.
5. Prints a per-document summary line and a final scanned/changed/added count plus a total-by-code breakdown.

## Usage Examples

```bash
herd php artisan rr:reparse-charges --dry-run
herd php artisan rr:reparse-charges --from=2026-05-01 --to=2026-07-31
herd php artisan rr:reparse-charges --id=101 --id=102
```

## Related Components

- **Model**: `RrDocument`, `RrCharge`
- **Service**: `RrParserService`, `RrPdfTextExtractor` (via `RrPdfTextExtractorContract`)
- **Related command**: `rr:backfill-penalty-snapshots` — typically run after this command to promote newly inserted penalty-code `rr_charges` rows into `rr_penalty_snapshots`

## Notes

- Insert-only by design: does not touch existing `rr_charges` rows, so re-running is idempotent (already-present codes are skipped by the existing-codes comparison).
- Does not classify codes as penalty vs. non-penalty or write to `rr_penalty_snapshots` — see `rr:backfill-penalty-snapshots` for that step.
