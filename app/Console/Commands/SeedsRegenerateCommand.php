<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ModelRegistry;
use App\Services\SeedSpecGenerator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class SeedsRegenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seeds:regenerate
                            {--check : Check mode - only report what would change}
                            {--model= : Regenerate specific model only}
                            {--force : Force regeneration even if custom code exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate seeder and JSON files from seed specs';

    /**
     * Execute the console command.
     */
    public function handle(SeedSpecGenerator $generator, ModelRegistry $registry): int
    {
        $checkMode = $this->option('check');
        $specificModel = $this->option('model');
        $force = $this->option('force');

        $models = $specificModel
            ? ["App\\Models\\{$specificModel}"]
            : $registry->getAllModels();

        if (empty($models)) {
            $this->info('No models found.');

            return self::SUCCESS;
        }

        $this->info('Regenerating seeders and JSON from specs...');
        $this->newLine();

        $hasChanges = false;

        foreach ($models as $modelClass) {
            $modelName = class_basename($modelClass);
            $spec = $generator->loadSpec($modelClass);

            if ($spec === null) {
                $this->warn("  {$modelName}: No spec found (run seeds:spec-sync first)");

                continue;
            }

            try {
                // Regenerate JSON file
                $jsonChanges = $this->regenerateJson($modelName, $spec, $checkMode, $force);

                // Regenerate seeder skeleton
                $seederChanges = $this->regenerateSeeder($modelName, $spec, $checkMode, $force);

                if ($jsonChanges || $seederChanges) {
                    $hasChanges = true;
                } else {
                    $this->line("  {$modelName}: Up to date");
                }
            } catch (Exception $e) {
                $this->error("  {$modelName}: Error - {$e->getMessage()}");
            }
        }

        $this->newLine();

        if ($checkMode && $hasChanges) {
            $this->warn('Files would be regenerated. Run without --check to apply changes.');

            return self::FAILURE;
        }

        $this->info('Regeneration complete.');

        return self::SUCCESS;
    }

    /**
     * Regenerate JSON file from spec.
     */
    private function regenerateJson(string $modelName, array $spec, bool $checkMode, bool $force): bool
    {
        $jsonKey = Str::snake(Str::plural($modelName));
        $jsonPath = database_path("seeders/data/{$jsonKey}.json");

        $fields = $spec['fields'] ?? [];
        $existingData = [];

        if (File::exists($jsonPath)) {
            $existingContent = File::get($jsonPath);
            $existingData = json_decode($existingContent, true) ?? [];
        }

        // Build new JSON structure
        $newData = [
            $jsonKey => [],
        ];

        // Preserve existing entries if they exist
        if (isset($existingData[$jsonKey]) && is_array($existingData[$jsonKey]) && ! empty($existingData[$jsonKey])) {
            // Keep existing data but ensure all fields are present
            foreach ($existingData[$jsonKey] as $entry) {
                $updatedEntry = $entry;

                // Add missing fields with defaults
                foreach ($fields as $field => $fieldSpec) {
                    if (! isset($updatedEntry[$field]) && $fieldSpec['default'] !== null) {
                        $updatedEntry[$field] = $fieldSpec['default'];
                    }
                }

                $newData[$jsonKey][] = $updatedEntry;
            }
        } else {
            // Create example entry
            $example = [];

            foreach ($fields as $field => $fieldSpec) {
                if (in_array($field, ['id', 'created_at', 'updated_at'], true)) {
                    continue;
                }

                $example[$field] = $this->generateExampleValue($field, $fieldSpec, $spec['value_hints'] ?? []);
            }

            $newData[$jsonKey][] = $example;
        }

        $newContent = json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $existingContent = File::exists($jsonPath) ? File::get($jsonPath) : '';

        if ($newContent !== $existingContent) {
            if ($checkMode) {
                $this->warn("  {$modelName}: JSON would be updated");
            } else {
                File::put($jsonPath, $newContent);
                $this->info("  {$modelName}: JSON regenerated");
            }

            return true;
        }

        return false;
    }

    /**
     * Regenerate seeder skeleton from spec.
     */
    private function regenerateSeeder(string $modelName, array $spec, bool $checkMode, bool $force): bool
    {
        // Find seeder in any category
        $categories = ['essential', 'development', 'production'];
        $seederPath = null;
        $category = null;

        foreach ($categories as $cat) {
            $path = database_path("seeders/{$cat}/{$modelName}Seeder.php");

            if (File::exists($path)) {
                $seederPath = $path;
                $category = $cat;
                break;
            }
        }

        if ($seederPath === null) {
            // Seeder doesn't exist yet - would be created by make:model:full
            return false;
        }

        $content = File::get($seederPath);

        // Check for protected regions
        if (Str::contains($content, '// GENERATED START') && Str::contains($content, '// GENERATED END')) {
            // Extract custom code outside protected regions
            $beforeGenerated = Str::before($content, '// GENERATED START');
            $afterGenerated = Str::after($content, '// GENERATED END');

            // Generate new protected region
            $generatedCode = $this->generateSeederCode($modelName, $spec, $category);

            $newContent = $beforeGenerated."// GENERATED START\n{$generatedCode}\n    // GENERATED END".$afterGenerated;

            if ($newContent !== $content) {
                if ($checkMode) {
                    $this->warn("  {$modelName}: Seeder would be updated");
                } else {
                    File::put($seederPath, $newContent);
                    $this->info("  {$modelName}: Seeder regenerated");
                }

                return true;
            }
        } elseif ($force) {
            // No protected regions - would overwrite (only with --force)
            $this->warn("  {$modelName}: Seeder has no protected regions - skipping (use --force to overwrite)");
        }

        return false;
    }

    /**
     * Generate seeder code from spec.
     */
    private function generateSeederCode(string $modelName, array $spec, string $category): string
    {
        $relationships = $spec['relationships'] ?? [];
        $code = '';

        // Generate relationship seeding
        if (! empty($relationships)) {
            $code .= "        // Seed relationships\n";

            foreach ($relationships as $relName => $relSpec) {
                if ($relSpec['type'] === 'belongsTo' && isset($relSpec['model'])) {
                    $relatedModel = $relSpec['model'];
                    $code .= "        if (\\App\\Models\\{$relatedModel}::query()->count() === 0) {\n";
                    $code .= "            \\App\\Models\\{$relatedModel}::factory()->count(5)->create();\n";
                    $code .= "        }\n";
                }
            }

            $code .= "\n";
        }

        return $code;
    }

    /**
     * Generate example value for a field.
     *
     * @param  array<string, mixed>  $fieldSpec
     * @param  array<string, mixed>  $valueHints
     */
    private function generateExampleValue(string $field, array $fieldSpec, array $valueHints): mixed
    {
        // Check value hints first
        if (isset($valueHints[$field])) {
            return $valueHints[$field]['example'] ?? null;
        }

        // Use default if available
        if ($fieldSpec['default'] !== null) {
            return $fieldSpec['default'];
        }

        // Generate based on type
        $type = $fieldSpec['type'] ?? 'string';

        return match ($type) {
            'string' => Str::contains($field, 'email') ? 'example@example.com' : 'Example '.$field,
            'integer', 'bigint' => 1,
            'boolean' => false,
            'datetime', 'timestamp' => '2024-01-01 00:00:00',
            'text' => 'Example text',
            default => null,
        };
    }
}
