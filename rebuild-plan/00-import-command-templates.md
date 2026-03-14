# Import Command Templates & Conventions

Every `fusion:import-*` Artisan command follows this spec. Chief must implement ALL import commands using this structure.

---

## Base Import Command Structure

All import commands extend a shared pattern:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportContactsCommand extends Command
{
    protected $signature = 'fusion:import-contacts
                            {--dry-run : Preview without writing to DB}
                            {--chunk=500 : Rows per DB transaction chunk}
                            {--since= : Only import rows updated_at >= this ISO8601 date}
                            {--force : Re-import existing rows (updateOrCreate)}';

    protected $description = 'Import contacts from MySQL legacy DB into PostgreSQL';

    // Override in each command
    protected string $sourceTable  = 'leads';
    protected string $targetTable  = 'contacts';
    protected string $importLogKey = 'contacts';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunk  = (int) $this->option('chunk');
        $since  = $this->option('since');
        $force  = $this->option('force');

        $this->info($dryRun ? '[DRY RUN] ' . $this->description : $this->description);

        $query = DB::connection('mysql_legacy')->table($this->sourceTable);
        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        $total     = $query->count();
        $processed = 0;
        $skipped   = 0;
        $failed    = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunk($chunk, function ($rows) use (
            $dryRun, $force, &$processed, &$skipped, &$failed, $bar
        ) {
            DB::beginTransaction();
            try {
                foreach ($rows as $row) {
                    try {
                        $mapped = $this->mapRow((array) $row);
                        if ($mapped === null) {
                            $skipped++;
                            continue; // Row explicitly skipped (see mapRow docs)
                        }
                        if (! $dryRun) {
                            $this->upsertRow($mapped, $force);
                        }
                        $processed++;
                    } catch (Throwable $e) {
                        $failed++;
                        Log::warning("fusion:import [{$this->importLogKey}] row {$row->id} failed: {$e->getMessage()}");
                        // Do NOT rethrow — continue processing other rows
                    }
                    $bar->advance();
                }
                if (! $dryRun) {
                    DB::commit();
                }
            } catch (Throwable $e) {
                DB::rollBack();
                $this->error("Chunk failed: {$e->getMessage()}");
                $failed += count($rows);
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Done. Processed: {$processed} | Skipped: {$skipped} | Failed: {$failed}");

        if ($failed > 0) {
            $this->warn("Some rows failed. Check laravel.log for details.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Map a legacy MySQL row to the new PostgreSQL schema.
     * Return null to SKIP a row (e.g. deleted, test data, excluded category).
     * Throw an exception to log + continue on unexpected data.
     */
    protected function mapRow(array $row): ?array
    {
        // Override in each command
        return $row;
    }

    /**
     * Upsert the mapped row into the target table.
     * Default: updateOrCreate by legacy_id (or similar business key).
     */
    protected function upsertRow(array $data, bool $force): void
    {
        // Override in each command
        DB::table($this->targetTable)->updateOrInsert(
            ['legacy_lead_id' => $data['legacy_lead_id']],
            $data
        );
    }
}
```

---

## Lead ID → Contact ID Map (Persistent)

**All import commands after Step 1 MUST use the contact ID map. It is stored in the DB, not in memory.**

```php
/**
 * Build the lead_id → contact_id map from contacts.legacy_lead_id.
 * Returns: array<int, int>  [mysql_lead_id => pgsql_contact_id]
 */
protected function buildLeadContactMap(): array
{
    return DB::table('contacts')
        ->whereNotNull('legacy_lead_id')
        ->pluck('id', 'legacy_lead_id')   // ['lead_id' => 'contact_id']
        ->all();
}

// Usage inside mapRow():
//   $map = $this->buildLeadContactMap();
//   $contactId = $map[$row['lead_id']] ?? null;
//   if ($contactId === null) { throw new \Exception("No contact for lead_id {$row['lead_id']}"); }
```

**Why `legacy_lead_id` column (not a separate mapping table):**
- No extra JOIN or lookup table needed
- The column is indexed: `$table->index('legacy_lead_id')`
- Step 25 two-way sync uses it for reverse mapping: `Contact::pluck('legacy_lead_id', 'id')`

---

## Per-Command Implementation Notes

### fusion:import-contacts (Step 1)
```
Source:  mysql_legacy.leads + mysql_legacy.contacts (contactable)
Target:  pgsql.contacts, contact_emails, contact_phones
Key:     legacy_lead_id = leads.id
Special: Set contact_origin = 'property'. Build the lead_id→contact_id map here (store via legacy_lead_id column).
Skip:    leads.is_test = 1 OR leads.deleted_at IS NOT NULL (if soft-deleted in source).
```

### fusion:import-projects-lots (Step 3)
```
Source:  mysql_legacy.projects, mysql_legacy.lots (and developers, project_types, states, suburbs)
Target:  pgsql.projects, lots, developers, projecttypes, states, suburbs
Key:     projects.legacy_id = mysql.projects.id; lots.legacy_id = mysql.lots.id
Special: Geocode lat/lng via laravel-geo-genius on import if lat/lng blank.
         Run scout:import after migration (or queue indexing job).
Skip:    projects.deleted_at IS NOT NULL.
```

### fusion:import-users (Step 2)
```
Source:  mysql_legacy.users
Target:  pgsql.users (update existing by email; create if not exists)
Key:     user.email (match)
Special: After matching user, set user.contact_id via lead_id→contact_id map (user.lead_id → contact.id).
         Create one organization per subscriber user; set org.owner_id = user.id.
Skip:    users.role = 'superadmin' (keep manual or seed separately).
```

### fusion:import-reservations-sales (Step 4)
```
Source:  mysql_legacy.property_reservations, property_enquiries, property_searches, sales, commissions
Target:  pgsql.property_reservations, property_enquiries, property_searches, sales, commissions
Key:     legacy_id columns (store mysql.id in legacy_id)
Special: Map all lead_id, purchaser_id, agent_id → contact_id via lead_id→contact_id map.
         Map mysql.lot_id → pgsql.lots.legacy_id → pgsql.lots.id
         Map mysql.project_id → pgsql.projects.legacy_id → pgsql.projects.id
         For commissions: migrate into new typed structure (commission_type enum).
Skip:    sales.is_test = 1 (if field exists).
```

### fusion:import-tasks-relationships-marketing (Step 5)
```
Source:  mysql_legacy.tasks, relationships, partners, mail_lists, notes, comments, tags, addresses, ...
Target:  All new pgsql tables
Key:     Most tables: legacy_id = mysql.id
Special: tasks: map assigned_id, attached_id → contact_id (via lead_id map).
         notes: noteable_type=Lead → Contact, noteable_id → contact_id.
         tags: import into spatie/laravel-tags (use Tag::findOrCreate(); Taggable pivot).
         Mail lists: client_ids (JSON array of lead_ids) → insert rows into mail_list_contacts with contact_id.
         partners: lead_id → contact_id (see Partners section below).
Skip:    Tags with no taggable (orphan records).
```

### fusion:import-ai-bot-config (Step 6)
```
Source:  mysql_legacy.ai_bot_categories (11), ai_bot_prompt_commands (481), ai_bot_boxes (46)
Target:  pgsql.ai_bot_categories, ai_bot_prompt_commands (→ ai_bot_prompts), ai_bots
Key:     slug (upsert by slug)
Special: Map ai_bot_boxes → ai_bots; ai_bot_prompt_commands → ai_bot_prompts.
         Set is_system = true for all imported bots/categories.
         Set organization_id = null (shared system bots, not org-specific).
Skip:    None — import all.
```

---

## Idempotency Rules

Every import command must be **safely re-runnable** (idempotent):

1. **updateOrCreate** on a stable business key (legacy_id / legacy_lead_id / email / slug).
2. Never use raw `INSERT` — always `updateOrInsert` or `upsert`.
3. Mapping (`buildLeadContactMap()`) is always read fresh from the DB each run.
4. If a row fails, it is logged and skipped — the next run will retry it (unless already imported).
5. `--dry-run` mode NEVER writes to DB; always works on a transaction that is rolled back.

---

## Verification Commands

Every import command has a paired verification:

```bash
php artisan fusion:verify-import-contacts
php artisan fusion:verify-import-projects-lots
php artisan fusion:verify-import-users
php artisan fusion:verify-import-reservations-sales
php artisan fusion:verify-import-tasks-relationships-marketing
php artisan fusion:verify-import-ai-bot-config
```

Each verification command must:
1. Compare row counts against expected values from `11-verification-per-step.md`
2. Check for orphan foreign keys (e.g. contact_id IS NULL where it must not be)
3. Verify the lead_id→contact_id map completeness (Step 1: all leads have a contact)
4. Output: `PASS ✅` or `FAIL ❌` per check + summary counts

Example output:
```
=== fusion:verify-import-contacts ===
✅ contacts: 9,678 rows (expected 9,678)
✅ contact_emails: 15,234 rows (expected ~15,000)
✅ contact_phones: 4,840 rows (expected ~5,000)
✅ legacy_lead_id map: 9,678 / 9,678 leads resolved (100%)
✅ No orphan contact_emails (all have valid contact_id)
OVERALL: PASS ✅
```

---

## mysql_legacy Connection Config

Add to `config/database.php` connections array:

```php
'mysql_legacy' => [
    'driver'    => 'mysql',
    'host'      => env('DB_LEGACY_HOST', '127.0.0.1'),
    'port'      => env('DB_LEGACY_PORT', '3306'),
    'database'  => env('DB_LEGACY_DATABASE', 'fusioncrmv3'),
    'username'  => env('DB_LEGACY_USERNAME', 'root'),
    'password'  => env('DB_LEGACY_PASSWORD', ''),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
    'strict'    => false, // Legacy DB may have non-strict data
    'options'   => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
],
```

Add to `.env` (and `.env.example`):
```
DB_LEGACY_HOST=127.0.0.1
DB_LEGACY_PORT=3306
DB_LEGACY_DATABASE=fusioncrmv3
DB_LEGACY_USERNAME=root
DB_LEGACY_PASSWORD=
```

**Test the connection before running imports:**
```bash
php artisan db:show --database=mysql_legacy
```

---

## Error Handling Rules

| Situation | Behaviour |
|---|---|
| Row FK not found (e.g. `lead_id` not in map) | Log warning, **skip row**, continue |
| Row DB constraint violation (duplicate key) | Use `updateOrInsert` — should not happen if idempotency is correct |
| Chunk DB transaction fails | Rollback chunk, log error, increment failed count, continue with next chunk |
| Fatal error (DB connection lost) | Command exits non-zero; resume with `--since=` flag next run |
| Test data row | Skip (filter in `mapRow()` with a `return null`) |
| NULL required field | Skip row, log warning with row id |

---

## Quick Reference

```bash
# Full imports (in order after Step 0)
php artisan fusion:import-projects-lots          # Step 3 (run before contacts to have lot/project IDs)
php artisan fusion:import-contacts               # Step 1 (builds lead_id→contact_id map)
php artisan fusion:import-users                  # Step 2
php artisan fusion:import-reservations-sales     # Step 4
php artisan fusion:import-tasks-relationships-marketing  # Step 5
php artisan fusion:import-ai-bot-config          # Step 6

# With options
php artisan fusion:import-contacts --dry-run     # Preview
php artisan fusion:import-contacts --chunk=1000  # Larger chunks for fast servers
php artisan fusion:import-contacts --force       # Re-import (updateOrCreate)

# Verification (run immediately after each import)
php artisan fusion:verify-import-contacts
php artisan fusion:verify-import-projects-lots
# ...etc

# Scout indexing (run after each import that has Searchable models)
php artisan scout:import "App\Models\Contact"
php artisan scout:import "App\Models\Project"
php artisan scout:import "App\Models\Lot"
php artisan scout:import "App\Models\Sale"
```
