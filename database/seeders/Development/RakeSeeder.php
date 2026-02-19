<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * Rake seeder. Demo data is created by RakeManagementDemoSeeder.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class RakeSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: rakes are seeded by RakeManagementDemoSeeder.
    }
}
