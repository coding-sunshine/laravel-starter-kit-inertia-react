<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrade activity_log table from spatie/laravel-activitylog v4 to v5.
 *
 * v5 schema changes:
 *   - Add `attribute_changes` (json, nullable) — tracked model changes
 *   - `properties` (json, nullable) — now exclusively for custom user data
 *   - Drop `batch_uuid` column (batch system removed)
 *
 * Backfill rule:
 *   v4 stored `{attributes, old}` inside `properties`. v5 moves that pair into
 *   `attribute_changes` and leaves the rest behind in `properties`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        if (! Schema::hasColumn('activity_log', 'attribute_changes')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->json('attribute_changes')->nullable()->after('causer_id');
            });
        }

        // Backfill attribute_changes from properties['attributes'|'old'] and
        // strip those keys out of properties so it only holds user data.
        DB::table('activity_log')
            ->whereNotNull('properties')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $properties = json_decode((string) $row->properties, true) ?? [];

                    if (! is_array($properties)) {
                        continue;
                    }

                    $hasAttributes = array_key_exists('attributes', $properties);
                    $hasOld = array_key_exists('old', $properties);

                    if (! $hasAttributes && ! $hasOld) {
                        continue;
                    }

                    $changes = [];
                    if ($hasAttributes) {
                        $changes['attributes'] = $properties['attributes'];
                        unset($properties['attributes']);
                    }
                    if ($hasOld) {
                        $changes['old'] = $properties['old'];
                        unset($properties['old']);
                    }

                    DB::table('activity_log')
                        ->where('id', $row->id)
                        ->update([
                            'attribute_changes' => json_encode($changes),
                            'properties' => $properties === [] ? null : json_encode($properties),
                        ]);
                }
            });

        if (Schema::hasColumn('activity_log', 'batch_uuid')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->dropColumn('batch_uuid');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        if (! Schema::hasColumn('activity_log', 'batch_uuid')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->uuid('batch_uuid')->nullable()->after('properties');
            });
        }

        // Reverse backfill: move attribute_changes back into properties.
        DB::table('activity_log')
            ->whereNotNull('attribute_changes')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $changes = json_decode((string) $row->attribute_changes, true) ?? [];
                    $properties = json_decode((string) ($row->properties ?? '{}'), true) ?? [];

                    if (! is_array($changes) || ! is_array($properties)) {
                        continue;
                    }

                    $merged = array_merge($properties, $changes);

                    DB::table('activity_log')
                        ->where('id', $row->id)
                        ->update([
                            'properties' => $merged === [] ? null : json_encode($merged),
                        ]);
                }
            });

        if (Schema::hasColumn('activity_log', 'attribute_changes')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                $table->dropColumn('attribute_changes');
            });
        }
    }
};
