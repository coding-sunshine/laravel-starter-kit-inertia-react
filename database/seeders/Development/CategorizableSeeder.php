<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * Categorizable trait seeder.
 *
 * Categorizable is a trait (App\Models\Concerns\Categorizable), not an Eloquent model.
 * Categories are seeded via CategorySeeder. This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class CategorizableSeeder extends Seeder
{
    /**
     * Run the database seeds (idempotent).
     */
    public function run(): void
    {
        // No-op: Categorizable is a trait; category data is seeded via CategorySeeder.
    }
}
