<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionClass;

final class SeedSpecGenerator
{
    /**
     * Generate seed spec for a model.
     *
     * @return array<string, mixed>
     */
    public function generateSpec(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            throw new InvalidArgumentException("Model class {$modelClass} does not exist");
        }

        $model = new $modelClass;
        $table = $model->getTable();
        $reflection = new ReflectionClass($modelClass);

        $spec = [
            'model' => class_basename($modelClass),
            'table' => $table,
            'fields' => $this->extractFields($table, $reflection),
            'relationships' => $this->extractRelationships($reflection),
            'value_hints' => $this->generateValueHints($table, $reflection),
            'scenarios' => [],
        ];

        return $spec;
    }

    /**
     * Load existing seed spec.
     *
     * @return array<string, mixed>|null
     */
    public function loadSpec(string $modelClass): ?array
    {
        $modelName = class_basename($modelClass);
        $specPath = database_path("seeders/specs/{$modelName}.json");

        if (! File::exists($specPath)) {
            return null;
        }

        $content = File::get($specPath);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Save seed spec.
     */
    public function saveSpec(string $modelClass, array $spec): void
    {
        $modelName = class_basename($modelClass);
        $specsDir = database_path('seeders/specs');

        if (! File::isDirectory($specsDir)) {
            File::makeDirectory($specsDir, 0755, true);
        }

        $specPath = "{$specsDir}/{$modelName}.json";

        File::put($specPath, json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Compare two specs and return differences.
     *
     * @return array<string, mixed>
     */
    public function diffSpecs(array $oldSpec, array $newSpec): array
    {
        $diff = [
            'added_fields' => [],
            'removed_fields' => [],
            'changed_fields' => [],
            'added_relationships' => [],
            'removed_relationships' => [],
            'needs_approval' => [],
        ];

        // Compare fields
        $oldFields = $oldSpec['fields'] ?? [];
        $newFields = $newSpec['fields'] ?? [];

        foreach ($newFields as $field => $fieldSpec) {
            if (! isset($oldFields[$field])) {
                $diff['added_fields'][$field] = $fieldSpec;

                // If new field is non-nullable without default, needs approval
                if (! $fieldSpec['nullable'] && $fieldSpec['default'] === null) {
                    $diff['needs_approval'][] = "New non-nullable field '{$field}' without default";
                }
            } elseif ($oldFields[$field] !== $fieldSpec) {
                $diff['changed_fields'][$field] = [
                    'old' => $oldFields[$field],
                    'new' => $fieldSpec,
                ];

                // If field became non-nullable, needs approval
                if ($oldFields[$field]['nullable'] && ! $fieldSpec['nullable']) {
                    $diff['needs_approval'][] = "Field '{$field}' became non-nullable";
                }
            }
        }

        foreach ($oldFields as $field => $fieldSpec) {
            if (! isset($newFields[$field])) {
                $diff['removed_fields'][$field] = $fieldSpec;
            }
        }

        // Compare relationships
        $oldRelationships = $oldSpec['relationships'] ?? [];
        $newRelationships = $newSpec['relationships'] ?? [];

        foreach ($newRelationships as $rel => $relSpec) {
            if (! isset($oldRelationships[$rel])) {
                $diff['added_relationships'][$rel] = $relSpec;
            }
        }

        foreach ($oldRelationships as $rel => $relSpec) {
            if (! isset($newRelationships[$rel])) {
                $diff['removed_relationships'][$rel] = $relSpec;
            }
        }

        return $diff;
    }

    /**
     * Extract fields from table schema.
     *
     * @return array<string, array{type: string, nullable: bool, default: mixed}>
     */
    private function extractFields(string $table, ReflectionClass $reflection): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $columns = Schema::getColumnListing($table);
        $fields = [];

        foreach ($columns as $column) {
            $type = Schema::getColumnType($table, $column);

            try {
                $columnInfo = Schema::getConnection()->getDoctrineColumn($table, $column);
                $nullable = ! $columnInfo->getNotnull();
                $default = $columnInfo->getDefault();
            } catch (Exception $e) {
                // Fallback for SQLite or other drivers that don't support Doctrine introspection
                $nullable = true; // Default to nullable if we can't determine
                $default = null;
            }

            $fields[$column] = [
                'type' => $type,
                'nullable' => $nullable,
                'default' => $default,
            ];

            // Check for enum values (only if we have column info)
            if (isset($columnInfo) && $type === 'string' && method_exists($columnInfo, 'getType')) {
                $doctrineType = $columnInfo->getType();
                if ($doctrineType instanceof \Doctrine\DBAL\Types\EnumType) {
                    $fields[$column]['enum'] = $doctrineType->getSQLDeclaration([], Schema::getConnection()->getDoctrineSchemaManager());
                }
            }
        }

        return $fields;
    }

    /**
     * Extract relationships from model reflection.
     *
     * @return array<string, array{type: string, model: string|null}>
     */
    private function extractRelationships(ReflectionClass $reflection): array
    {
        $relationships = [];
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            $returnType = $method->getReturnType();

            if ($returnType === null) {
                continue;
            }

            $returnTypeName = $returnType->getName();

            // Check if it's a relationship method
            if (! is_subclass_of($returnTypeName, \Illuminate\Database\Eloquent\Relations\Relation::class)) {
                continue;
            }

            $methodName = $method->getName();

            // Infer relationship type from method name or return type
            $type = $this->inferRelationshipType($returnTypeName, $methodName);

            if ($type !== null) {
                $relationships[$methodName] = [
                    'type' => $type,
                    'model' => null, // Will be inferred from return type if possible
                ];
            }
        }

        return $relationships;
    }

    /**
     * Infer relationship type from return type and method name.
     */
    private function inferRelationshipType(string $returnType, string $methodName): ?string
    {
        if (Str::contains($returnType, 'BelongsTo')) {
            return 'belongsTo';
        }

        if (Str::contains($returnType, 'HasMany')) {
            return 'hasMany';
        }

        if (Str::contains($returnType, 'HasOne')) {
            return 'hasOne';
        }

        if (Str::contains($returnType, 'BelongsToMany')) {
            return 'belongsToMany';
        }

        if (Str::contains($returnType, 'HasOneThrough')) {
            return 'hasOneThrough';
        }

        if (Str::contains($returnType, 'HasManyThrough')) {
            return 'hasManyThrough';
        }

        // Fallback: infer from method name
        if (Str::startsWith($methodName, 'belongsTo')) {
            return 'belongsTo';
        }

        if (Str::startsWith($methodName, 'hasMany')) {
            return 'hasMany';
        }

        return null;
    }

    /**
     * Generate value hints for fields.
     *
     * @return array<string, mixed>
     */
    private function generateValueHints(string $table, ReflectionClass $reflection): array
    {
        $hints = [];

        // Check casts for hints
        if ($reflection->hasMethod('casts')) {
            $castsMethod = $reflection->getMethod('casts');

            if ($castsMethod->isPublic() || $castsMethod->isProtected()) {
                try {
                    $model = $reflection->newInstanceWithoutConstructor();
                    $casts = $castsMethod->invoke($model);

                    foreach ($casts as $field => $cast) {
                        if ($cast === 'datetime') {
                            $hints[$field] = ['type' => 'datetime', 'example' => '2024-01-01 00:00:00'];
                        } elseif ($cast === 'hashed') {
                            $hints[$field] = ['type' => 'password', 'example' => 'password'];
                        } elseif ($cast === 'boolean') {
                            $hints[$field] = ['type' => 'boolean', 'example' => true];
                        }
                    }
                } catch (Exception $e) {
                    // Ignore if we can't invoke
                }
            }
        }

        // Add common field hints
        if (Schema::hasTable($table)) {
            $columns = Schema::getColumnListing($table);

            foreach ($columns as $column) {
                if (Str::contains($column, 'email')) {
                    $hints[$column] = ['type' => 'email', 'example' => 'user@example.com'];
                } elseif (Str::contains($column, 'name')) {
                    $hints[$column] = ['type' => 'name', 'example' => 'John Doe'];
                } elseif (Str::contains($column, 'url')) {
                    $hints[$column] = ['type' => 'url', 'example' => 'https://example.com'];
                }
            }
        }

        return $hints;
    }
}
