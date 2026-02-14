<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('laravel-subscriptions.tables.plans'), function (Blueprint $table): void {
            $table->boolean('is_per_seat')->default(false)->after('price');
            $table->decimal('price_per_seat', 10, 2)->default('0.00')->after('is_per_seat');
        });

        Schema::table(config('laravel-subscriptions.tables.subscriptions'), function (Blueprint $table): void {
            $table->string('gateway_subscription_id')->nullable()->after('slug');
            $table->unsignedInteger('quantity')->default(1)->after('gateway_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::table(config('laravel-subscriptions.tables.plans'), function (Blueprint $table): void {
            $table->dropColumn(['is_per_seat', 'price_per_seat']);
        });

        Schema::table(config('laravel-subscriptions.tables.subscriptions'), function (Blueprint $table): void {
            $table->dropColumn(['gateway_subscription_id', 'quantity']);
        });
    }
};
