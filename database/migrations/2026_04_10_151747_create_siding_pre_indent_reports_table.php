<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siding_pre_indent_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siding_id')->nullable()->constrained('sidings')->nullOnDelete();
            $table->date('report_date');
            $table->unsignedInteger('total_indent_raised');
            $table->unsignedInteger('indent_available');
            $table->text('loading_status_text')->nullable();
            $table->text('indent_details_text')->nullable();
            $table->timestamps();

            $table->index('report_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siding_pre_indent_reports');
    }
};
