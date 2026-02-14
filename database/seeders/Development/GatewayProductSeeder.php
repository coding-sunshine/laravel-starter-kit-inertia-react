<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * GatewayProduct seeder.
 *
 * Gateway products are typically created when configuring payment gateways.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class GatewayProductSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: gateway products are configured at runtime.
    }
}
