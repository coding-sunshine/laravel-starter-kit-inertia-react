<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ImportProjectsLotsCommand extends Command
{
    protected $signature = 'fusion:import-projects-lots
                            {--dry-run : Preview without writing to DB}
                            {--chunk=500 : Rows per DB transaction chunk}
                            {--since= : Only import rows updated_at >= this ISO8601 date}
                            {--force : Re-import existing rows (updateOrCreate)}
                            {--table=all : Which tables to import: all, projects, lots, developers, projecttypes, states, suburbs, project_updates, flyers, flyer_templates}';

    protected $description = 'Import projects, lots, and related tables from MySQL legacy DB into PostgreSQL';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $since = $this->option('since');
        $force = (bool) $this->option('force');
        $table = $this->option('table');

        if ($dryRun) {
            $this->info('[DRY RUN] '.$this->description);
        } else {
            $this->info($this->description);
        }

        $overallFailed = 0;

        if ($table === 'all' || $table === 'states') {
            $overallFailed += $this->importStates($dryRun, $chunk, $since, $force);
        }

        if ($table === 'all' || $table === 'suburbs') {
            $overallFailed += $this->importSuburbs($dryRun, $chunk, $since, $force);
        }

        if ($table === 'all' || $table === 'developers') {
            $overallFailed += $this->importDevelopers($dryRun, $chunk, $since, $force);
        }

        if ($table === 'all' || $table === 'projecttypes') {
            $overallFailed += $this->importProjecttypes($dryRun, $chunk, $since, $force);
        }

        if ($table === 'all' || $table === 'projects') {
            $overallFailed += $this->importProjects($dryRun, $chunk, $since, $force);
        }

        if ($table === 'all' || $table === 'lots') {
            $overallFailed += $this->importLots($dryRun, $chunk, $since, $force);
        }

        if ($table === 'all' || $table === 'project_updates') {
            $overallFailed += $this->importProjectUpdates($dryRun, $chunk, $since, $force);
        }

        if ($table === 'all' || $table === 'flyer_templates') {
            $overallFailed += $this->importFlyerTemplates($dryRun, $chunk, $since, $force);
        }

        if ($table === 'all' || $table === 'flyers') {
            $overallFailed += $this->importFlyers($dryRun, $chunk, $since, $force);
        }

        if ($overallFailed > 0) {
            $this->warn('Some rows failed. Check laravel.log for details.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function importStates(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Importing states...');

        return $this->runImport(
            sourceTable: 'states',
            targetTable: 'states',
            logKey: 'states',
            dryRun: $dryRun,
            chunk: $chunk,
            since: $since,
            mapRow: function (array $row): ?array {
                return [
                    'legacy_id' => $row['id'],
                    'name' => $row['name'] ?? '',
                    'abbreviation' => $row['abbreviation'] ?? $row['abbr'] ?? null,
                    'country' => $row['country'] ?? 'AU',
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertRow: function (array $data, bool $force): void {
                DB::table('states')->updateOrInsert(
                    ['legacy_id' => $data['legacy_id']],
                    $data
                );
            },
            force: $force,
        );
    }

    private function importSuburbs(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Importing suburbs...');

        // Build state legacy_id → new state_id map
        $stateMap = DB::table('states')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        return $this->runImport(
            sourceTable: 'suburbs',
            targetTable: 'suburbs',
            logKey: 'suburbs',
            dryRun: $dryRun,
            chunk: $chunk,
            since: $since,
            mapRow: function (array $row) use ($stateMap): ?array {
                $stateId = null;
                if (! empty($row['state_id'])) {
                    $stateId = $stateMap[$row['state_id']] ?? null;
                }

                return [
                    'legacy_id' => $row['id'],
                    'name' => $row['name'] ?? '',
                    'postcode' => $row['postcode'] ?? null,
                    'state_id' => $stateId,
                    'lat' => $row['lat'] ?? null,
                    'lng' => $row['lng'] ?? null,
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertRow: function (array $data, bool $force): void {
                DB::table('suburbs')->updateOrInsert(
                    ['legacy_id' => $data['legacy_id']],
                    $data
                );
            },
            force: $force,
        );
    }

    private function importDevelopers(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Importing developers...');

        return $this->runImport(
            sourceTable: 'developers',
            targetTable: 'developers',
            logKey: 'developers',
            dryRun: $dryRun,
            chunk: $chunk,
            since: $since,
            mapRow: function (array $row): ?array {
                return [
                    'legacy_id' => $row['id'],
                    'name' => $row['name'] ?? '',
                    'slug' => $row['slug'] ?? null,
                    'website' => $row['website'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'email' => $row['email'] ?? null,
                    'description' => $row['description'] ?? null,
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertRow: function (array $data, bool $force): void {
                DB::table('developers')->updateOrInsert(
                    ['legacy_id' => $data['legacy_id']],
                    $data
                );
            },
            force: $force,
        );
    }

    private function importProjecttypes(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Importing project types...');

        return $this->runImport(
            sourceTable: 'projecttypes',
            targetTable: 'projecttypes',
            logKey: 'projecttypes',
            dryRun: $dryRun,
            chunk: $chunk,
            since: $since,
            mapRow: function (array $row): ?array {
                return [
                    'legacy_id' => $row['id'],
                    'name' => $row['name'] ?? '',
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertRow: function (array $data, bool $force): void {
                DB::table('projecttypes')->updateOrInsert(
                    ['legacy_id' => $data['legacy_id']],
                    $data
                );
            },
            force: $force,
        );
    }

    private function importProjects(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Importing projects...');

        // Build developer legacy_id → new developer_id map
        $developerMap = DB::table('developers')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        // Build projecttype legacy_id → new projecttype_id map
        $projecttypeMap = DB::table('projecttypes')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        return $this->runImport(
            sourceTable: 'projects',
            targetTable: 'projects',
            logKey: 'projects',
            dryRun: $dryRun,
            chunk: $chunk,
            since: $since,
            mapRow: function (array $row) use ($developerMap, $projecttypeMap): ?array {
                // Skip soft-deleted projects
                if (! empty($row['deleted_at'])) {
                    return null;
                }

                $developerId = null;
                if (! empty($row['developer_id'])) {
                    $developerId = $developerMap[$row['developer_id']] ?? null;
                }

                $projecttypeId = null;
                if (! empty($row['project_type_id'])) {
                    $projecttypeId = $projecttypeMap[$row['project_type_id']] ?? null;
                }

                return [
                    'legacy_id' => $row['id'],
                    'title' => $row['title'] ?? 'Untitled Project',
                    'stage' => $row['stage'] ?? 'selling',
                    'estate' => $row['estate'] ?? null,
                    'total_lots' => $row['total_lots'] ?? null,
                    'storeys' => $row['storeys'] ?? null,
                    'min_landsize' => $row['min_landsize'] ?? null,
                    'max_landsize' => $row['max_landsize'] ?? null,
                    'living_area' => $row['living_area'] ?? null,
                    'bedrooms' => is_numeric($row['bedrooms'] ?? null) ? (int) $row['bedrooms'] : ((int) ($row['bedrooms'] ?? 0) ?: null),
                    'bathrooms' => is_numeric($row['bathrooms'] ?? null) ? (int) $row['bathrooms'] : ((int) ($row['bathrooms'] ?? 0) ?: null),
                    'garage' => is_numeric($row['garage'] ?? null) ? (int) $row['garage'] : ((int) ($row['garage'] ?? 0) ?: null),
                    'min_rent' => $row['min_rent'] ?? null,
                    'max_rent' => $row['max_rent'] ?? null,
                    'avg_rent' => $row['avg_rent'] ?? null,
                    'rent_yield' => $row['rent_yield'] ?? null,
                    'is_hot_property' => (bool) ($row['is_hot_property'] ?? false),
                    'description' => $row['description'] ?? null,
                    'min_price' => $row['min_price'] ?? null,
                    'max_price' => $row['max_price'] ?? null,
                    'avg_price' => $row['avg_price'] ?? null,
                    'body_corporate_fees' => $row['body_corporate_fees'] ?? null,
                    'rates_fees' => $row['rates_fees'] ?? null,
                    'is_archived' => (bool) ($row['is_archived'] ?? false),
                    'is_hidden' => (bool) ($row['is_hidden'] ?? false),
                    'start_at' => $row['start_at'] ?? null,
                    'end_at' => $row['end_at'] ?? null,
                    'is_smsf' => (bool) ($row['is_smsf'] ?? false),
                    'is_firb' => (bool) ($row['is_firb'] ?? false),
                    'is_ndis' => (bool) ($row['is_ndis'] ?? false),
                    'is_cashflow_positive' => (bool) ($row['is_cashflow_positive'] ?? false),
                    'build_time' => $row['build_time'] ?? null,
                    'historical_growth' => $row['historical_growth'] ?? null,
                    'land_info' => $row['land_info'] ?? null,
                    'developer_id' => $developerId,
                    'projecttype_id' => $projecttypeId,
                    'lat' => $row['lat'] ?? null,
                    'lng' => $row['lng'] ?? null,
                    'is_featured' => (bool) ($row['is_featured'] ?? false),
                    'featured_order' => $row['featured_order'] ?? null,
                    'is_co_living' => (bool) ($row['is_co_living'] ?? false),
                    'is_high_cap_growth' => (bool) ($row['is_high_cap_growth'] ?? false),
                    'is_rooming' => (bool) ($row['is_rooming'] ?? false),
                    'is_rent_to_sell' => (bool) ($row['is_rent_to_sell'] ?? false),
                    'is_exclusive' => (bool) ($row['is_exclusive'] ?? false),
                    'suburb' => $row['suburb'] ?? null,
                    'state' => $row['state'] ?? null,
                    'postcode' => $row['postcode'] ?? null,
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertRow: function (array $data, bool $force): void {
                DB::table('projects')->updateOrInsert(
                    ['legacy_id' => $data['legacy_id']],
                    $data
                );
            },
            force: $force,
        );
    }

    private function importLots(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Importing lots...');

        // Build project legacy_id → new project_id map
        $projectMap = DB::table('projects')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        return $this->runImport(
            sourceTable: 'lots',
            targetTable: 'lots',
            logKey: 'lots',
            dryRun: $dryRun,
            chunk: $chunk,
            since: $since,
            mapRow: function (array $row) use ($projectMap): ?array {
                // Skip soft-deleted lots
                if (! empty($row['deleted_at'])) {
                    return null;
                }

                $projectId = $projectMap[$row['project_id']] ?? null;
                if ($projectId === null) {
                    return null; // Skip orphan lots — project was deleted or not imported
                }

                return [
                    'legacy_id' => $row['id'],
                    'project_id' => $projectId,
                    'title' => $row['title'] ?? null,
                    'land_price' => $this->numericOrNull($row['land_price'] ?? null),
                    'build_price' => $this->numericOrNull($row['build_price'] ?? null),
                    'stage' => $row['stage'] ?? null,
                    'level' => $row['level'] ?? null,
                    'building' => $row['building'] ?? null,
                    'floorplan' => $row['floorplan'] ?? null,
                    'car' => is_numeric($row['car'] ?? null) ? (int) $row['car'] : null,
                    'storage' => $row['storage'] ?? null,
                    'view' => $row['view'] ?? null,
                    'garage' => is_numeric($row['garage'] ?? null) ? (int) $row['garage'] : null,
                    'aspect' => $row['aspect'] ?? null,
                    'internal' => $this->numericOrNull($row['internal'] ?? null),
                    'external' => $this->numericOrNull($row['external'] ?? null),
                    'total' => $this->numericOrNull($row['total'] ?? null),
                    'storeys' => is_numeric($row['storeys'] ?? null) ? (int) $row['storeys'] : null,
                    'land_size' => $this->numericOrNull($row['land_size'] ?? null),
                    'title_status' => $row['title_status'] ?? 'available',
                    'living_area' => $this->numericOrNull($row['living_area'] ?? null),
                    'price' => $this->numericOrNull($row['price'] ?? null),
                    'bedrooms' => is_numeric($row['bedrooms'] ?? null) ? (int) $row['bedrooms'] : null,
                    'bathrooms' => is_numeric($row['bathrooms'] ?? null) ? (int) $row['bathrooms'] : null,
                    'study' => is_numeric($row['study'] ?? null) ? (int) $row['study'] : null,
                    'mpr' => (bool) ($row['mpr'] ?? false),
                    'powder_room' => (bool) ($row['powder_room'] ?? false),
                    'balcony' => $this->numericOrNull($row['balcony'] ?? null),
                    'rent_yield' => $this->numericOrNull($row['rent_yield'] ?? null),
                    'weekly_rent' => $this->numericOrNull($row['weekly_rent'] ?? null),
                    'rates' => $this->numericOrNull($row['rates'] ?? null),
                    'body_corporation' => $this->numericOrNull($row['body_corporation'] ?? null),
                    'is_archived' => (bool) ($row['is_archived'] ?? false),
                    'is_nras' => (bool) ($row['is_nras'] ?? false),
                    'is_smsf' => (bool) ($row['is_smsf'] ?? false),
                    'is_cashflow_positive' => (bool) ($row['is_cashflow_positive'] ?? false),
                    'completion' => $this->safeDateOrNull($row['completion'] ?? null),
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertRow: function (array $data, bool $force): void {
                DB::table('lots')->updateOrInsert(
                    ['legacy_id' => $data['legacy_id']],
                    $data
                );
            },
            force: $force,
        );
    }

    private function importProjectUpdates(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Importing project updates...');

        // Build project legacy_id → new project_id map
        $projectMap = DB::table('projects')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        return $this->runImport(
            sourceTable: 'project_updates',
            targetTable: 'project_updates',
            logKey: 'project_updates',
            dryRun: $dryRun,
            chunk: $chunk,
            since: $since,
            mapRow: function (array $row) use ($projectMap): ?array {
                $projectId = $projectMap[$row['project_id']] ?? null;
                if ($projectId === null) {
                    return null; // Skip orphan updates
                }

                return [
                    'legacy_id' => $row['id'],
                    'project_id' => $projectId,
                    'user_id' => $row['user_id'] ?? null,
                    'content' => $row['content'] ?? $row['update'] ?? $row['body'] ?? '',
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertRow: function (array $data, bool $force): void {
                DB::table('project_updates')->updateOrInsert(
                    ['legacy_id' => $data['legacy_id']],
                    $data
                );
            },
            force: $force,
        );
    }

    private function importFlyerTemplates(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Importing flyer templates...');

        return $this->runImport(
            sourceTable: 'flyer_templates',
            targetTable: 'flyer_templates',
            logKey: 'flyer_templates',
            dryRun: $dryRun,
            chunk: $chunk,
            since: $since,
            mapRow: function (array $row): ?array {
                return [
                    'legacy_id' => $row['id'],
                    'name' => $row['name'] ?? 'Template',
                    'html_content' => $row['html_content'] ?? $row['content'] ?? null,
                    'css_content' => $row['css_content'] ?? null,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertRow: function (array $data, bool $force): void {
                DB::table('flyer_templates')->updateOrInsert(
                    ['legacy_id' => $data['legacy_id']],
                    $data
                );
            },
            force: $force,
        );
    }

    private function importFlyers(bool $dryRun, int $chunk, ?string $since, bool $force): int
    {
        $this->info('Importing flyers...');

        // Build project legacy_id → new project_id map
        $projectMap = DB::table('projects')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        // Build lot legacy_id → new lot_id map
        $lotMap = DB::table('lots')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        // Build flyer_template legacy_id → new template_id map
        $templateMap = DB::table('flyer_templates')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->all();

        return $this->runImport(
            sourceTable: 'flyers',
            targetTable: 'flyers',
            logKey: 'flyers',
            dryRun: $dryRun,
            chunk: $chunk,
            since: $since,
            mapRow: function (array $row) use ($projectMap, $lotMap, $templateMap): ?array {
                $projectId = $projectMap[$row['project_id']] ?? null;
                if ($projectId === null) {
                    return null; // Skip orphan flyers
                }

                $lotId = null;
                if (! empty($row['lot_id'])) {
                    $lotId = $lotMap[$row['lot_id']] ?? null;
                }

                $templateId = null;
                if (! empty($row['template_id'])) {
                    $templateId = $templateMap[$row['template_id']] ?? null;
                }

                return [
                    'legacy_id' => $row['id'],
                    'flyer_template_id' => $templateId,
                    'project_id' => $projectId,
                    'lot_id' => $lotId,
                    'notes' => $row['notes'] ?? null,
                    'is_custom' => (bool) ($row['is_custom'] ?? false),
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
            },
            upsertRow: function (array $data, bool $force): void {
                DB::table('flyers')->updateOrInsert(
                    ['legacy_id' => $data['legacy_id']],
                    $data
                );
            },
            force: $force,
        );
    }

    private function numericOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '' || $value === ' ') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function safeDateOrNull(?string $value): ?string
    {
        if ($value === null || mb_trim($value) === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function runImport(
        string $sourceTable,
        string $targetTable,
        string $logKey,
        bool $dryRun,
        int $chunk,
        ?string $since,
        Closure $mapRow,
        Closure $upsertRow,
        bool $force,
    ): int {
        $query = DB::connection('mysql_legacy')->table($sourceTable);

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
            $dryRun, $force, $mapRow, $upsertRow, &$processed, &$skipped, &$failed, $bar, $logKey
        ) {
            foreach ($rows as $row) {
                try {
                    $mapped = $mapRow((array) $row);
                    if ($mapped === null) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }
                    if (! $dryRun) {
                        DB::transaction(fn () => $upsertRow($mapped, $force));
                    }
                    $processed++;
                } catch (Throwable $e) {
                    $failed++;
                    Log::warning("fusion:import [{$logKey}] row {$row->id} failed: {$e->getMessage()}");
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("  [{$logKey}] Processed: {$processed} | Skipped: {$skipped} | Failed: {$failed}");

        return $failed;
    }
}
