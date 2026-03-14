<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class ImportUsersCommand extends Command
{
    protected $signature = 'fusion:import-users
                            {--dry-run : Preview without writing to DB}
                            {--chunk=500 : Rows per DB transaction chunk}
                            {--since= : Only import rows updated_at >= this ISO8601 date}
                            {--force : Re-import existing rows (updateOrCreate)}';

    protected $description = 'Link users to contacts via legacy lead_id and create subscriber organizations from MySQL legacy DB.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $since = $this->option('since');

        if ($dryRun) {
            $this->info('[DRY RUN] '.$this->description);
        } else {
            $this->info($this->description);
        }

        $contactMap = $this->buildLeadContactMap();
        $this->info('Contact map loaded: '.count($contactMap).' entries.');

        $query = DB::connection('mysql_legacy')->table('users');
        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        $total = $query->count();
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunk($chunk, function ($rows) use (
            $dryRun, $contactMap, &$processed, &$skipped, &$failed, $bar
        ) {
            DB::beginTransaction();
            try {
                foreach ($rows as $row) {
                    try {
                        $row = (array) $row;

                        // Skip superadmin users — kept manual or seeded separately
                        if (($row['role'] ?? null) === 'superadmin') {
                            $skipped++;
                            $bar->advance();

                            continue;
                        }

                        $newUser = DB::table('users')->where('email', $row['email'])->first();
                        if (! $newUser) {
                            $skipped++;
                            $bar->advance();

                            continue;
                        }

                        if (! $dryRun) {
                            // Link contact via lead_id → contact_id map
                            $leadId = $row['lead_id'] ?? null;
                            $contactId = $leadId ? ($contactMap[$leadId] ?? null) : null;

                            DB::table('users')->where('id', $newUser->id)->update([
                                'contact_id' => $contactId,
                                'updated_at' => now(),
                            ]);

                            // Create one organization per subscriber user
                            if (($row['role'] ?? null) === 'subscriber') {
                                $this->ensureSubscriberOrganization((object) $newUser, $contactId, $dryRun);
                            }
                        }

                        $processed++;
                    } catch (Throwable $e) {
                        $failed++;
                        Log::warning("fusion:import-users row {$row['id']} failed: {$e->getMessage()}");
                    }
                    $bar->advance();
                }
                if (! $dryRun) {
                    DB::commit();
                }
            } catch (Throwable $e) {
                DB::rollBack();
                $this->error('Chunk failed: '.$e->getMessage());
                $failed += count($rows);
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Done. Processed: {$processed} | Skipped: {$skipped} | Failed: {$failed}");

        if ($failed > 0) {
            $this->warn('Some rows failed. Check laravel.log for details.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Build the lead_id → contact_id map from contacts.legacy_lead_id.
     *
     * @return array<int, int>
     */
    private function buildLeadContactMap(): array
    {
        return DB::table('contacts')
            ->whereNotNull('legacy_lead_id')
            ->pluck('id', 'legacy_lead_id')
            ->all();
    }

    /**
     * Create exactly one organization for a subscriber user (idempotent).
     */
    private function ensureSubscriberOrganization(object $newUser, ?int $contactId, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        $contact = $contactId
            ? DB::table('contacts')->where('id', $contactId)->first()
            : null;

        $orgName = ($contact?->company_name ?? null)
            ?? (($contact ? mb_trim(($contact->first_name ?? '').' '.($contact->last_name ?? '')) : null) ? mb_trim(($contact->first_name ?? '').' '.($contact->last_name ?? ''))."'s Org" : null)
            ?? $newUser->name."'s Org";

        $org = Organization::query()->firstOrCreate(
            ['owner_id' => $newUser->id],
            [
                'name' => $orgName,
                'slug' => Str::slug($orgName),
                'created_at' => $newUser->created_at,
                'updated_at' => now(),
            ]
        );

        // Add user as member of their own org (idempotent), is_default = true
        $org->users()->syncWithoutDetaching([
            $newUser->id => ['is_default' => true],
        ]);
    }
}
