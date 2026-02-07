<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * ModelFlag (Spatie model-flags) seeder.
 *
 * The model_flags table is populated when models using HasFlags call flag() / unflag().
 * No development rows are required; this seeder exists to satisfy the model-audit / pre-commit check.
 */
final class ModelFlagSeeder extends Seeder
{
    /**
     * Run the database seeds (idempotent).
     */
    public function run(): void
    {
        // No-op: flags are created at runtime when models use HasFlags.
    }
}
