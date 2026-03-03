<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\Contact;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ImportMediaCommand extends Command
{
    /**
     * Legacy model_type → new model_type mapping.
     *
     * @var array<string, string>
     */
    private const MODEL_TYPE_MAP = [
        'App\\Models\\Lead' => 'App\\Models\\Contact',
        'App\\Models\\Project' => 'App\\Models\\Project',
        'App\\Models\\Lot' => 'App\\Models\\Lot',
        'App\\Models\\User' => 'App\\Models\\User',
        'App\\Models\\Sale' => 'App\\Models\\Sale',
        'App\\Models\\PropertyReservation' => 'App\\Models\\PropertyReservation',
    ];

    protected $signature = 'fusion:import-media
                            {--fresh : Truncate media table first}
                            {--chunk=500 : Chunk size for processing}';

    protected $description = 'Import media records from MySQL legacy (Spatie media library format). Maps model_type from legacy to new names. Physical files must be synced separately (filesystem/S3 copy).';

    /** @var array<int, int> legacy lead id => new contact id */
    private array $leadToContactMap = [];

    public function handle(): int
    {
        $connection = 'mysql_legacy';

        try {
            DB::connection($connection)->getPdo();
        } catch (Throwable $e) {
            $this->error('Legacy MySQL connection failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->leadToContactMap = Contact::withoutGlobalScope(OrganizationScope::class)
            ->whereNotNull('legacy_lead_id')
            ->pluck('id', 'legacy_lead_id')
            ->all();

        $this->info('Lead→Contact map loaded: '.count($this->leadToContactMap).' entries.');

        if ($this->option('fresh')) {
            $this->warn('Truncating media table...');
            DB::table('media')->delete();
        }

        $this->importMedia($connection);

        $this->info('Import complete. Media records: '.DB::table('media')->count());
        $this->warn('Note: Physical files must be synced separately (filesystem/S3 copy is an ops task).');

        return self::SUCCESS;
    }

    private function importMedia(string $connection): void
    {
        $this->info('Importing media records...');
        $chunkSize = (int) $this->option('chunk');
        $total = (int) DB::connection($connection)->table('media')->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        $imported = 0;
        $skipped = 0;

        DB::connection($connection)->table('media')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use ($bar, &$imported, &$skipped): void {
                foreach ($rows as $row) {
                    $modelType = (string) $row->model_type;
                    $modelId = (int) $row->model_id;

                    // Map model_type
                    $newModelType = self::MODEL_TYPE_MAP[$modelType] ?? $modelType;

                    // Map model_id for Contact types
                    if ($modelType === 'App\\Models\\Lead') {
                        $contactId = $this->leadToContactMap[$modelId] ?? null;
                        if ($contactId === null) {
                            $skipped++;
                            $bar->advance();

                            continue;
                        }
                        $modelId = $contactId;
                    }

                    $customProperties = $row->custom_properties ?? null;
                    if (is_string($customProperties)) {
                        $customProperties = json_decode($customProperties, true);
                    }

                    $responsiveImages = $row->responsive_images ?? null;
                    if (is_string($responsiveImages)) {
                        $responsiveImages = json_decode($responsiveImages, true);
                    }

                    $generatedConversions = $row->generated_conversions ?? null;
                    if (is_string($generatedConversions)) {
                        $generatedConversions = json_decode($generatedConversions, true);
                    }

                    DB::table('media')->insertOrIgnore([
                        'id' => $row->id,
                        'model_type' => $newModelType,
                        'model_id' => $modelId,
                        'uuid' => $row->uuid ?? null,
                        'collection_name' => $row->collection_name ?? 'default',
                        'name' => $row->name ?? '',
                        'file_name' => $row->file_name ?? '',
                        'mime_type' => $row->mime_type ?? null,
                        'disk' => $row->disk ?? 'public',
                        'conversions_disk' => $row->conversions_disk ?? $row->disk ?? 'public',
                        'size' => $row->size ?? 0,
                        'manipulations' => is_string($row->manipulations ?? null) ? $row->manipulations : json_encode($row->manipulations ?? []),
                        'custom_properties' => is_array($customProperties) ? json_encode($customProperties) : json_encode([]),
                        'generated_conversions' => is_array($generatedConversions) ? json_encode($generatedConversions) : json_encode([]),
                        'responsive_images' => is_array($responsiveImages) ? json_encode($responsiveImages) : json_encode([]),
                        'order_column' => $row->order_column ?? null,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);

                    $imported++;
                    $bar->advance();
                }

                unset($rows);
                if (function_exists('gc_mem_caches')) {
                    gc_mem_caches();
                }
            }, 'id', 'id');

        $bar->finish();
        $this->newLine();
        $this->line("  Imported: {$imported}, Skipped: {$skipped}");
    }
}
