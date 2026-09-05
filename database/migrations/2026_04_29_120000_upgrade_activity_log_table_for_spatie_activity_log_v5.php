<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('activitylog.database_connection');
        $table = config('activitylog.table_name');

        $schema = Schema::connection($connection);

        if (! $schema->hasTable($table)) {
            return;
        }

        if (! $schema->hasColumn($table, 'attribute_changes')) {
            $schema->table($table, function (Blueprint $blueprint) use ($table, $schema): void {
                $after = $schema->hasColumn($table, 'event')
                    ? 'event'
                    : ($schema->hasColumn($table, 'causer_id') ? 'causer_id' : 'description');
                $blueprint->json('attribute_changes')->nullable()->after($after);
            });
        }

        $driver = Schema::connection($connection)->getConnection()->getDriverName();
        $baseQuery = DB::connection($connection)->table($table)->orderBy('id');

        if ($driver === 'sqlite') {
            foreach ($baseQuery->get() as $row) {
                $this->migrateRow($connection, $table, $row);
            }
        } else {
            $baseQuery->chunkById(500, function ($rows) use ($connection, $table): void {
                foreach ($rows as $row) {
                    $this->migrateRow($connection, $table, $row);
                }
            });
        }

        if ($schema->hasColumn($table, 'batch_uuid')) {
            $schema->table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('batch_uuid');
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Reverting Spatie activity log v5 schema changes is not supported.');
    }

    private function migrateRow(?string $connection, string $table, object $row): void
    {
        $raw = $row->properties ?? null;
        if ($raw === null || $raw === '') {
            return;
        }
        $props = is_string($raw) ? json_decode($raw, true) : (array) $raw;
        if (! is_array($props)) {
            return;
        }
        $changes = array_intersect_key($props, array_flip(['attributes', 'old']));
        $remaining = array_diff_key($props, array_flip(['attributes', 'old']));
        if ($changes === [] && $remaining === $props) {
            return;
        }
        DB::connection($connection)->table($table)->where('id', $row->id)->update([
            'attribute_changes' => $changes === [] ? null : json_encode($changes),
            'properties' => $remaining === [] ? null : json_encode($remaining),
        ]);
    }
};
