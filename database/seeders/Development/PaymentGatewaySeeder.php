<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * PaymentGateway seeder.
 *
 * Payment gateways are typically configured via settings.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        // No-op: payment gateways are configured at runtime.
    }
}
