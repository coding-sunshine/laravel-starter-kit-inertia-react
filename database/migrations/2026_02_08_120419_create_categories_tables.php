<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\NestedSet;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('type')->default('default');
            NestedSet::columns($table);
            $table->timestamps();
        });

        Schema::create('categoryables', function (Blueprint $table): void {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('categoryable_type');
            $table->unsignedBigInteger('categoryable_id');
            $table->primary(['category_id', 'categoryable_type', 'categoryable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categoryables');
        Schema::dropIfExists('categories');
    }
};
