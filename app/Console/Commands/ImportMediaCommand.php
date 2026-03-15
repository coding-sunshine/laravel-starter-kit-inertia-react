<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ImportMediaCommand extends Command
{
    /**
     * V3 model_type → [v4 model class, legacy_id column, v4 table]
     *
     * @var array<string, array{class: string, column: string, table: string}>
     */
    private const array MODEL_MAP = [
        'App\\Models\\Lead' => ['class' => 'App\\Models\\Contact', 'column' => 'legacy_lead_id', 'table' => 'contacts'],
        'App\\Models\\Project' => ['class' => 'App\\Models\\Project', 'column' => 'legacy_id', 'table' => 'projects'],
        'App\\Models\\Lot' => ['class' => 'App\\Models\\Lot', 'column' => 'legacy_id', 'table' => 'lots'],
        'App\\Models\\Sale' => ['class' => 'App\\Models\\Sale', 'column' => 'legacy_id', 'table' => 'sales'],
        'App\\Models\\Flyer' => ['class' => 'App\\Models\\Flyer', 'column' => 'legacy_id', 'table' => 'flyers'],
        'App\\Models\\Comment' => ['class' => 'App\\Models\\CrmComment', 'column' => 'legacy_id', 'table' => 'crm_comments'],
        'App\\Models\\Website' => ['class' => 'App\\Models\\WordpressWebsite', 'column' => 'legacy_id', 'table' => 'wordpress_websites'],
        'App\\Models\\CampaignWebsite' => ['class' => 'App\\Models\\CampaignWebsite', 'column' => 'legacy_id', 'table' => 'campaign_websites'],
        'App\\Models\\PropertyReservation' => ['class' => 'App\\Models\\PropertyReservation', 'column' => 'legacy_id', 'table' => 'property_reservations'],
        'App\\Models\\PropertySearch' => ['class' => 'App\\Models\\PropertySearch', 'column' => 'legacy_id', 'table' => 'property_searches'],
        'App\\Models\\Resource' => ['class' => 'App\\Models\\CrmResource', 'column' => 'legacy_id', 'table' => 'crm_resources'],
        'App\\Models\\PropertyEnquiry' => ['class' => 'App\\Models\\PropertyEnquiry', 'column' => 'legacy_id', 'table' => 'property_enquiries'],
        'App\\Models\\AiBotBox' => ['class' => 'App\\Models\\AiBot', 'column' => 'legacy_id', 'table' => 'ai_bots'],
        'App\\Models\\WordpressWebsite' => ['class' => 'App\\Models\\WordpressWebsite', 'column' => 'legacy_id', 'table' => 'wordpress_websites'],
    ];

    /**
     * V3 model types to skip (no v4 equivalent).
     *
     * @var array<int, string>
     */
    private const array SKIP_MODELS = [
        'App\\Models\\AdManagement',
        'App\\Models\\WebsiteElement',
    ];

    protected $signature = 'fusion:import-media
                            {--dry-run : Preview without writing to DB}
                            {--chunk=500 : Rows per DB transaction chunk}
                            {--model-type=all : Which model type to import media for}
                            {--force : Re-import existing rows}';

    protected $description = 'Import media records from MySQL legacy DB into PostgreSQL. Updates model_type and model_id mappings — S3 files stay in place.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $modelType = $this->option('model-type');
        $force = (bool) $this->option('force');

        if ($dryRun) {
            $this->info('[DRY RUN] '.$this->description);
        } else {
            $this->info($this->description);
        }

        // Build ID maps for all model types
        $idMaps = $this->buildIdMaps();

        $query = DB::connection('mysql_legacy')->table('media');

        if ($modelType !== 'all') {
            $query->where('model_type', $modelType);
        }

        $total = $query->count();
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $this->info("Total legacy media records: {$total}");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunk($chunk, function ($rows) use ($dryRun, $idMaps, &$processed, &$skipped, &$failed, $bar) {
            $batch = [];

            foreach ($rows as $row) {
                try {
                    $v3ModelType = $row->model_type;

                    if (in_array($v3ModelType, self::SKIP_MODELS, true)) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    $mapping = self::MODEL_MAP[$v3ModelType] ?? null;
                    if ($mapping === null) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    $v4ModelClass = $mapping['class'];
                    $v4ModelId = $idMaps[$v3ModelType][(int) $row->model_id] ?? null;

                    if ($v4ModelId === null) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    $batch[] = [
                        'model_type' => $v4ModelClass,
                        'model_id' => $v4ModelId,
                        'uuid' => $row->uuid ?? null,
                        'collection_name' => $row->collection_name ?? 'default',
                        'name' => $row->name ?? '',
                        'file_name' => $row->file_name ?? '',
                        'mime_type' => $row->mime_type ?? null,
                        'disk' => $row->disk ?? 's3',
                        'conversions_disk' => $row->conversions_disk ?? $row->disk ?? 's3',
                        'size' => $row->size ?? 0,
                        'manipulations' => $row->manipulations ?? '[]',
                        'custom_properties' => $row->custom_properties ?? '[]',
                        'generated_conversions' => $row->generated_conversions ?? '[]',
                        'responsive_images' => $row->responsive_images ?? '[]',
                        'order_column' => $row->order_column ?? null,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ];

                    $processed++;
                } catch (Throwable $e) {
                    $failed++;
                    Log::warning("fusion:import-media row {$row->id} failed: {$e->getMessage()}");
                }

                $bar->advance();
            }

            if (! $dryRun && $batch !== []) {
                try {
                    DB::table('media')->insert($batch);
                } catch (Throwable $e) {
                    // Fall back to one-by-one insert on batch failure
                    foreach ($batch as $record) {
                        try {
                            DB::table('media')->insert($record);
                        } catch (Throwable $innerE) {
                            $failed++;
                            $processed--;
                            Log::warning("fusion:import-media single insert failed: {$innerE->getMessage()}");
                        }
                    }
                }
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Processed: {$processed} | Skipped: {$skipped} | Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Build ID maps for all model types: v3 model_id → v4 model_id.
     *
     * @return array<string, array<int, int>>
     */
    private function buildIdMaps(): array
    {
        $maps = [];

        foreach (self::MODEL_MAP as $v3Type => $mapping) {
            $table = $mapping['table'];
            $column = $mapping['column'];

            try {
                $maps[$v3Type] = DB::table($table)
                    ->whereNotNull($column)
                    ->pluck('id', $column)
                    ->map(fn ($id) => (int) $id)
                    ->all();
            } catch (Throwable) {
                $maps[$v3Type] = [];
                $this->warn("  Could not build ID map for {$table}.{$column}");
            }
        }

        return $maps;
    }
}
