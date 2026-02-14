<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * RefundRequest seeder.
 *
 * Refund requests are created when users request refunds.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class RefundRequestSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: refund requests are created at runtime.
    }
}
