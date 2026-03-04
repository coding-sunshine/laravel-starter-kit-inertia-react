<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * Organization seeder.
 *
 * Organizations are typically created when users register or create orgs.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: organizations are created at runtime.
    }
}
