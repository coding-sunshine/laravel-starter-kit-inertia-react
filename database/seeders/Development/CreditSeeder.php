<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * Credit seeder.
 *
 * Credits are typically granted via HasCredits::addCredits() at runtime.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class CreditSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: credits are added at runtime.
    }
}
