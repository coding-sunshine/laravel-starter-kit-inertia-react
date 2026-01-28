<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class RelationshipAnalyzer
{
    /**
     * Analyze migration file to detect relationships.
     *
     * @return array<string, array{type: string, model: string}>
     */
    public function analyzeMigration(string $migrationPath): array
    {
        if (! File::exists($migrationPath)) {
            return [];
        }

        $content = File::get($migrationPath);
        $relationships = [];

        // Detect foreign keys (belongsTo relationships)
        if (preg_match_all('/\$table->(?:foreign|unsignedBigInteger|unsignedInteger)\([\'"](\w+)[\'"]/', $content, $matches)) {
            foreach ($matches[1] as $foreignKey) {
                // Skip common Laravel fields
                if (in_array($foreignKey, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                    continue;
                }

                // Extract model name from foreign key (e.g., user_id -> User)
                if (Str::endsWith($foreignKey, '_id')) {
                    $modelName = Str::studly(Str::before($foreignKey, '_id'));
                    $relationships[$foreignKey] = [
                        'type' => 'belongsTo',
                        'model' => $modelName,
                    ];
                }
            }
        }

        // Detect pivot tables (many-to-many)
        if (preg_match('/\$table->(?:foreign|unsignedBigInteger)\([\'"](\w+)_id[\'"]/', $content, $matches)) {
            $tableName = basename($migrationPath);
            if (Str::contains($tableName, 'pivot') || preg_match('/\d+_\d+_create_\w+_table/', $tableName)) {
                // This might be a pivot table
                $relationships['_pivot'] = [
                    'type' => 'belongsToMany',
                    'model' => null,
                ];
            }
        }

        return $relationships;
    }

    /**
     * Get the latest migration for a model.
     */
    public function getLatestMigrationForModel(string $modelName): ?string
    {
        $migrationsPath = database_path('migrations');
        $tableName = Str::snake(Str::plural($modelName));

        if (! File::isDirectory($migrationsPath)) {
            return null;
        }

        $files = File::files($migrationsPath);
        $latest = null;
        $latestTimestamp = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();

            if (Str::contains($filename, "create_{$tableName}_table") || Str::contains($filename, "create_{$tableName}s_table")) {
                // Extract timestamp
                if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})/', $filename, $matches)) {
                    $timestamp = str_replace('_', '', $matches[1]);

                    if ($timestamp > $latestTimestamp) {
                        $latestTimestamp = $timestamp;
                        $latest = $file->getPathname();
                    }
                }
            }
        }

        return $latest;
    }

    /**
     * Generate seeder code that handles relationships.
     *
     * @param  array<string, array{type: string, model: string}>  $relationships
     */
    public function generateRelationshipSeederCode(array $relationships, string $modelName): string
    {
        if (empty($relationships)) {
            return '';
        }

        $code = "\n    /**\n     * Seed relationships.\n     */\n    private function seedRelationships(): void\n    {\n";

        foreach ($relationships as $foreignKey => $relationship) {
            if ($relationship['type'] === 'belongsTo' && isset($relationship['model'])) {
                $relatedModel = $relationship['model'];
                $code .= "        // Ensure {$relatedModel} exists for {$foreignKey}\n";
                $code .= "        if (\\App\\Models\\{$relatedModel}::query()->count() === 0) {\n";
                $code .= "            \\App\\Models\\{$relatedModel}::factory()->count(5)->create();\n";
                $code .= "        }\n\n";
            }
        }

        $code .= "    }\n";

        return $code;
    }
}
