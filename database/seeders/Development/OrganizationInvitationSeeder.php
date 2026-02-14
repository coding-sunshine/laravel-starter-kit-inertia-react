<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * OrganizationInvitation seeder.
 *
 * Invitations are created when members invite others to an organization.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class OrganizationInvitationSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: invitations are created at runtime.
    }
}
