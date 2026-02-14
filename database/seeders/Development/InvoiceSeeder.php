<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * Invoice seeder.
 *
 * Invoices are typically created by the payment gateway (Stripe, etc.).
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: invoices are created by payment gateway.
    }
}
