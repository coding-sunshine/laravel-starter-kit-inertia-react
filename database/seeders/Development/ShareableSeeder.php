<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

final class ShareableSeeder extends Seeder
{
    /**
     * Run the database seeds (idempotent).
     * Share records are created at runtime when users share items; no default dev data.
     */
    public function run(): void
    {
        // No-op: shareables are created via HasVisibility::shareWithOrganization / shareWithUser
    }
}
