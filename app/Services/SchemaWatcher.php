<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class SchemaWatcher
{
    /**
     * Detect changes in migrations since a given commit/branch.
     *
     * @return array<string>
     */
    public function detectMigrationChanges(?string $since = null): array
    {
        $migrationsPath = database_path('migrations');

        if (! File::isDirectory($migrationsPath)) {
            return [];
        }

        $files = File::files($migrationsPath);
        $changed = [];

        if ($since !== null) {
            // Use git to detect changed files
            $output = shell_exec("git diff --name-only {$since} -- database/migrations/ 2>/dev/null");

            if ($output) {
                $changedFiles = array_filter(explode("\n", mb_trim($output)));

                foreach ($changedFiles as $file) {
                    if (Str::endsWith($file, '.php')) {
                        $changed[] = $file;
                    }
                }
            }
        } else {
            // Return all migrations if no baseline
            foreach ($files as $file) {
                $changed[] = $file->getPathname();
            }
        }

        return $changed;
    }

    /**
     * Detect changes in model files since a given commit/branch.
     *
     * @return array<string>
     */
    public function detectModelChanges(?string $since = null): array
    {
        $modelsPath = app_path('Models');

        if (! File::isDirectory($modelsPath)) {
            return [];
        }

        $changed = [];

        if ($since !== null) {
            $output = shell_exec("git diff --name-only {$since} -- app/Models/ 2>/dev/null");

            if ($output) {
                $changedFiles = array_filter(explode("\n", mb_trim($output)));

                foreach ($changedFiles as $file) {
                    if (Str::endsWith($file, '.php')) {
                        $changed[] = $file;
                    }
                }
            }
        } else {
            $files = File::allFiles($modelsPath);

            foreach ($files as $file) {
                $changed[] = $file->getPathname();
            }
        }

        return $changed;
    }

    /**
     * Extract model name from migration file.
     */
    public function extractModelFromMigration(string $migrationPath): ?string
    {
        $content = File::get($migrationPath);
        $filename = basename($migrationPath);

        // Try to extract table name from migration
        if (preg_match('/create\([\'"](\w+)[\'"]/', $content, $matches)) {
            $tableName = $matches[1];

            // Convert table name to model name (e.g., users -> User)
            return Str::studly(Str::singular($tableName));
        }

        // Fallback: try to extract from filename
        if (preg_match('/create_(\w+)_table/', $filename, $matches)) {
            $tableName = $matches[1];

            return Str::studly(Str::singular($tableName));
        }

        return null;
    }

    /**
     * Get models affected by migration changes.
     *
     * @return array<string>
     */
    public function getAffectedModels(?string $since = null): array
    {
        $migrations = $this->detectMigrationChanges($since);
        $models = [];

        foreach ($migrations as $migration) {
            $model = $this->extractModelFromMigration($migration);

            if ($model !== null) {
                $modelClass = "App\\Models\\{$model}";

                if (class_exists($modelClass)) {
                    $models[] = $modelClass;
                }
            }
        }

        // Also check direct model changes
        $modelFiles = $this->detectModelChanges($since);

        foreach ($modelFiles as $modelFile) {
            $className = $this->getClassNameFromFile($modelFile);

            if ($className !== null && class_exists($className)) {
                $models[] = $className;
            }
        }

        return array_unique($models);
    }

    /**
     * Get class name from file path.
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = File::get($filePath);

        if (! preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            return null;
        }

        $namespace = $namespaceMatch[1];
        $className = basename($filePath, '.php');

        return "{$namespace}\\{$className}";
    }
}
