<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\AISeederCodeGenerator;
use App\Services\EnhancedRelationshipAnalyzer;
use App\Services\ModelRegistry;
use App\Services\SchemaWatcher;
use App\Services\SeedSpecGenerator;
use Exception;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MigrationListener
{
    /**
     * Handle the event.
     */
    public function handle(MigrationsEnded $event): void
    {
        // Only auto-sync if enabled in config
        if (! config('seeding.auto_sync_after_migrations', true)) {
            return;
        }

        try {
            $schemaWatcher = app(SchemaWatcher::class);
            $affectedModels = $schemaWatcher->getAffectedModels();

            if (empty($affectedModels)) {
                return;
            }

            // Run spec sync silently for affected models
            $specGenerator = app(SeedSpecGenerator::class);

            foreach ($affectedModels as $modelClass) {
                try {
                    $spec = $specGenerator->generateSpec($modelClass);
                    $oldSpec = $specGenerator->loadSpec($modelClass);

                    if ($oldSpec === null) {
                        // New spec - create it
                        $specGenerator->saveSpec($modelClass, $spec);
                    } else {
                        // Update spec with new fields
                        $diff = $specGenerator->diffSpecs($oldSpec, $spec);

                        if (! empty($diff['added_fields']) || ! empty($diff['changed_fields']) || ! empty($diff['added_relationships']) || ! empty($diff['removed_relationships'])) {
                            $updatedSpec = $oldSpec;
                            $updatedSpec['fields'] = $spec['fields'];
                            $updatedSpec['relationships'] = $spec['relationships'];
                            $updatedSpec['value_hints'] = array_merge($oldSpec['value_hints'] ?? [], $spec['value_hints']);

                            $specGenerator->saveSpec($modelClass, $updatedSpec);

                            // Auto-regenerate seeder if relationships changed significantly
                            if (! empty($diff['added_relationships']) || ! empty($diff['removed_relationships'])) {
                                $this->regenerateSeeder($modelClass, $updatedSpec);
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Silently continue - don't break migrations
                }
            }
        } catch (Exception $e) {
            // Silently fail - don't break migrations
        }
    }

    /**
     * Regenerate seeder when relationships change.
     *
     * @param  array<string, mixed>  $spec
     */
    private function regenerateSeeder(string $modelClass, array $spec): void
    {
        if (! config('seeding.auto_regenerate_seeders', true)) {
            return;
        }

        try {
            $modelName = class_basename($modelClass);
            $registry = app(ModelRegistry::class);
            $seederInfo = $registry->hasSeeder($modelClass);

            if (! $seederInfo['exists']) {
                return;
            }

            $category = $seederInfo['category'] ?? 'development';
            $seederPath = database_path("seeders/{$category}/{$modelName}Seeder.php");

            if (! File::exists($seederPath)) {
                return;
            }

            // Analyze relationships
            $enhancedAnalyzer = app(EnhancedRelationshipAnalyzer::class);
            $relationships = $enhancedAnalyzer->analyzeModel($modelClass);

            // Generate new seeder code
            $aiGenerator = app(AISeederCodeGenerator::class);
            $seederMethods = $aiGenerator->generateSeederCode($modelName, $spec, $relationships, $category);

            // Read existing seeder
            $content = File::get($seederPath);

            // Check for protected regions (custom code)
            if (str_contains($content, '// GENERATED START') && str_contains($content, '// GENERATED END')) {
                // Update only generated section
                $beforeGenerated = Str::before($content, '// GENERATED START');
                $afterGenerated = Str::after($content, '// GENERATED END');

                $newContent = $beforeGenerated."// GENERATED START\n{$seederMethods}\n    // GENERATED END".$afterGenerated;
            } else {
                // No protected regions - update entire seeder methods
                // Extract class structure
                if (preg_match('/(.*public function run\(\): void.*?\{.*?\n\s+\$this->seedRelationships\(\);\s+\$this->seedFromJson\(\);\s+\$this->seedFromFactory\(\);\s+\}\s+)(.*)(\})/s', $content, $matches)) {
                    $newContent = $matches[1].$seederMethods.$matches[3];
                } else {
                    // Fallback: append methods
                    $newContent = Str::beforeLast($content, '}')."\n{$seederMethods}\n}";
                }
            }

            File::put($seederPath, $newContent);
        } catch (Exception $e) {
            // Silently fail - don't break migrations
        }
    }
}
