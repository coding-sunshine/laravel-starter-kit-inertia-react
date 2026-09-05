<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * StockLedger seeder. Demo data is created by RakeManagementDemoSeeder.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class StockLedgerSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: stock ledger entries are seeded by RakeManagementDemoSeeder.
    }
}
