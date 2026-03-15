<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Integrations\Xero\XeroConnector;
use App\Models\Sale;
use App\Models\XeroConnection;
use App\Models\XeroInvoice;
use Illuminate\Support\Facades\Log;

final readonly class SyncInvoiceToXeroAction
{
    public function handle(Sale $sale, XeroConnection $connection): void
    {
        if (! XeroConnector::isConfigured()) {
            Log::warning('xero.deferred: XERO_CLIENT_ID not configured — SyncInvoiceToXeroAction skipped', [
                'sale_id' => $sale->id,
                'xero_connection_id' => $connection->id,
            ]);

            return;
        }

        XeroInvoice::query()->updateOrCreate(
            [
                'sale_id' => $sale->id,
                'xero_connection_id' => $connection->id,
            ],
            [
                'xero_invoice_id' => $sale->id.'-pending',
                'status' => 'DRAFT',
                'invoice_type' => 'ACCREC',
                'last_synced_at' => now(),
            ]
        );
    }
}
