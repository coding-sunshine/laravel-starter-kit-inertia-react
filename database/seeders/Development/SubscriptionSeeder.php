<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * Subscription seeder.
 *
 * Subscriptions are typically created via Stripe checkout or similar.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: subscriptions are created via payment flow.
    }
}
