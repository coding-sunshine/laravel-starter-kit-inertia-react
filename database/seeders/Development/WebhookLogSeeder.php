<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * WebhookLog seeder.
 *
 * Webhook logs are created when processing incoming webhooks.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class WebhookLogSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: webhook logs are created at runtime.
    }
}
