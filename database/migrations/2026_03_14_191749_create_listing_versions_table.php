<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_versions', function (Blueprint $table) {
            $table->id();
            $table->string('listable_type');
            $table->unsignedBigInteger('listable_id');
            $table->unsignedSmallInteger('version')->default(1);
            $table->jsonb('snapshot');
            $table->string('change_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['listable_type', 'listable_id']);
            $table->index(['listable_type', 'listable_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_versions');
    }
};
