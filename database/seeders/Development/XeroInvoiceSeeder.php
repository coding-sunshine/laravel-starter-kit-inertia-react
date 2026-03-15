<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\XeroConnection;
use App\Models\XeroInvoice;
use Illuminate\Database\Seeder;

final class XeroInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        if (XeroInvoice::query()->exists()) {
            return;
        }

        $connection = XeroConnection::query()->first();

        if (! $connection) {
            return;
        }

        XeroInvoice::query()->create([
            'xero_connection_id' => $connection->id,
            'xero_invoice_id' => 'xero-invoice-'.uniqid(),
            'invoice_number' => 'INV-0001',
            'amount' => 1500.00,
            'status' => 'AUTHORISED',
            'invoice_type' => 'ACCREC',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'last_synced_at' => now(),
        ]);
    }
}
