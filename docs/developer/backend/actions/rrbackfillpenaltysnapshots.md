# rr:backfill-penalty-snapshots

## Purpose

Console command. Backfills `rr_penalty_snapshots` for `rr_charges` rows carrying a penalty code that never became a snapshot because the import gate didn't recognize the code at the time (May 2026 parser regression). Insert-only: never dispatches notifications/events, never touches `applied_penalties`.

## Location

`app/Console/Commands/RrBackfillPenaltySnapshotsCommand.php`

## Signature

```
rr:backfill-penalty-snapshots
    {--dry-run : Report what would be inserted without writing to DB}
    {--from= : Only rr_charges whose RR document has rr_received_date on/after this date (Y-m-d)}
    {--to= : Only rr_charges whose RR document has rr_received_date on/before this date (Y-m-d)}
```

## Method Signature

```php
public function handle(RrImportService $importService): int
```

## Dependencies

- `App\Services\Railway\RrImportService` — `isPenaltyCode()` (now `public`) classifies a charge code as a penalty code

## Behavior

1. Queries `RrCharge` rows with `amount > 0`, eager-loading `rrDocument`, filterable by `--from`/`--to` on the related `RrDocument.rr_received_date`, chunked by 200.
2. For each charge: skips if `RrImportService::isPenaltyCode()` is false, or if the charge has no related `RrDocument`.
3. Skips if an `RrPenaltySnapshot` already exists for that `(rr_document_id, penalty_code)` pair (`skippedExisting` counter).
4. Unless `--dry-run`, inserts a new `RrPenaltySnapshot` row inside `DB::transaction()`:
    - `rake_id` from the RR document
    - `rake_charge_id` **left `null` intentionally** — `RrImportService::resolveOrCreatePenaltyCharge()`'s link recomputes a rake's aggregate `PENALTY` `RakeCharge` amount from a whole charge batch, which isn't safe to replay per-row for a historical backfill
    - `wagon_number` / `wagon_sequence` left `null` (not recoverable from `rr_charges`)
    - `meta` set to `['name' => $charge->charge_name]`
5. Prints a per-row summary line and a final inserted/skipped count plus a total-by-code breakdown.

## Usage Examples

```bash
herd php artisan rr:backfill-penalty-snapshots --dry-run
herd php artisan rr:backfill-penalty-snapshots --from=2026-05-01 --to=2026-07-31
```

## Related Components

- **Model**: `RrCharge`, `RrPenaltySnapshot`, `RrDocument`
- **Service**: `RrImportService` (`isPenaltyCode()`, `resolveChargeType()` — both made `public` for this command)
- **Related command**: `rr:reparse-charges` — run first to insert any missing `rr_charges` rows before backfilling snapshots from them

## Notes

- Idempotent: the existing-snapshot check prevents duplicate `rr_penalty_snapshots` rows on re-run.
- `rake_charge_id` staying `null` means these backfilled snapshots are not linked to a `RakeCharge` aggregate; downstream consumers that join through `rake_charge_id` will not see backfilled rows via that path.
