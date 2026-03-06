<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        /*
        |--------------------------------------------------------------------------
        | RAKES
        |--------------------------------------------------------------------------
        */

        Schema::table('rakes', function (Blueprint $table) {

            $table->string('data_source', 30)
                ->default('system')
                ->index();
        });

        /*
        |--------------------------------------------------------------------------
        | WAGONS
        |--------------------------------------------------------------------------
        */

        Schema::table('wagons', function (Blueprint $table) {

            $table->decimal('loaded_weight_mt', 10, 2)->nullable();
            $table->decimal('permissible_weight_mt', 10, 2)->nullable();
            $table->decimal('overload_weight_mt', 10, 2)->nullable();
        });

        /*
        |--------------------------------------------------------------------------
        | RR DOCUMENTS
        |--------------------------------------------------------------------------
        */

        Schema::table('rr_documents', function (Blueprint $table) {

            $table->string('data_source', 30)
                ->default('system')
                ->index();

            $table->decimal('distance_km', 8, 2)->nullable();

            $table->string('commodity_code', 50)->nullable();
            $table->string('commodity_description')->nullable();

            $table->string('invoice_number', 50)->nullable();
            $table->date('invoice_date')->nullable();
        });

        /*
        |--------------------------------------------------------------------------
        | RR CHARGES
        |--------------------------------------------------------------------------
        */

        Schema::create('rr_charges', function (Blueprint $table) {

            $table->id();

            $table->foreignId('rr_document_id')
                ->constrained('rr_documents')
                ->cascadeOnDelete();

            $table->string('charge_code', 20);
            $table->string('charge_name')->nullable();

            $table->decimal('amount', 14, 2);

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index('charge_code');
        });

    }

    public function down(): void
    {

        Schema::table('rakes', function (Blueprint $table) {
            $table->dropColumn('data_source');
        });

        Schema::table('wagons', function (Blueprint $table) {
            $table->dropColumn([
                'loaded_weight_mt',
                'permissible_weight_mt',
                'overload_weight_mt',
            ]);
        });

        Schema::table('rr_documents', function (Blueprint $table) {
            $table->dropColumn([
                'data_source',
                'distance_km',
                'commodity_code',
                'commodity_description',
                'invoice_number',
                'invoice_date',
            ]);
        });

        Schema::dropIfExists('rr_charges');
    }
};
