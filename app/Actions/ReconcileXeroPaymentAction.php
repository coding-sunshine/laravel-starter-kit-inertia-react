<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\XeroInvoice;
use App\Models\XeroReconciliation;
use Illuminate\Support\Facades\Log;

final readonly class ReconcileXeroPaymentAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void
    {
        $payments = $payload['payments'] ?? [$payload];

        foreach ($payments as $payment) {
            $xeroInvoiceId = $payment['Invoice']['InvoiceID'] ?? $payment['invoice_id'] ?? null;
            $xeroPaymentId = $payment['PaymentID'] ?? $payment['payment_id'] ?? null;

            if (! $xeroInvoiceId || ! $xeroPaymentId) {
                Log::warning('xero.reconcile: missing InvoiceID or PaymentID in payload', ['payment' => $payment]);

                continue;
            }

            $invoice = XeroInvoice::query()
                ->where('xero_invoice_id', $xeroInvoiceId)
                ->first();

            if (! $invoice) {
                Log::info('xero.reconcile: no local invoice found for xero_invoice_id', ['xero_invoice_id' => $xeroInvoiceId]);

                continue;
            }

            XeroReconciliation::query()->updateOrCreate(
                ['xero_payment_id' => $xeroPaymentId],
                [
                    'xero_invoice_id' => $invoice->id,
                    'amount' => (float) ($payment['Amount'] ?? $payment['amount'] ?? 0),
                    'payment_date' => $payment['Date'] ?? $payment['payment_date'] ?? null,
                    'reconciled_at' => now(),
                    'raw_payload' => $payment,
                ]
            );
        }
    }
}
