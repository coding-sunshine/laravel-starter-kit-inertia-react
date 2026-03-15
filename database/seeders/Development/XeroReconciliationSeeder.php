<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\XeroInvoice;
use App\Models\XeroReconciliation;
use Illuminate\Database\Seeder;

final class XeroReconciliationSeeder extends Seeder
{
    public function run(): void
    {
        if (XeroReconciliation::query()->exists()) {
            return;
        }

        $invoice = XeroInvoice::query()->first();

        if (! $invoice) {
            return;
        }

        XeroReconciliation::query()->create([
            'xero_invoice_id' => $invoice->id,
            'xero_payment_id' => 'xero-payment-'.uniqid(),
            'amount' => 1500.00,
            'payment_date' => now()->toDateString(),
            'reconciled_at' => now(),
            'raw_payload' => [
                'PaymentID' => 'xero-payment-demo',
                'Amount' => 1500.00,
                'Date' => now()->toDateString(),
            ],
        ]);
    }
}
