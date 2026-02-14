<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * OrganizationDomain seeder.
 *
 * Organization domains are configured when setting up custom domains.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class OrganizationDomainSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: organization domains are configured at runtime.
    }
}
