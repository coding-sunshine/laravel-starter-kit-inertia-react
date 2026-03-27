<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE production_entries ALTER COLUMN trip DROP NOT NULL;');
        DB::statement('ALTER TABLE production_entries ALTER COLUMN qty DROP NOT NULL;');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE production_entries ALTER COLUMN trip SET NOT NULL;');
        DB::statement('ALTER TABLE production_entries ALTER COLUMN qty SET NOT NULL;');
    }
};
