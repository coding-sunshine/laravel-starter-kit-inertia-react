<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('billing_email')->nullable()->after('owner_id');
            $table->string('tax_id')->nullable()->after('billing_email');
            $table->json('billing_address')->nullable()->after('tax_id');
            $table->string('stripe_customer_id')->nullable()->after('billing_address');
            $table->string('paddle_customer_id')->nullable()->after('stripe_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn([
                'billing_email',
                'tax_id',
                'billing_address',
                'stripe_customer_id',
                'paddle_customer_id',
            ]);
        });
    }
};
