<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Integrations\Xero\XeroConnector;
use App\Models\Contact;
use App\Models\XeroConnection;
use App\Models\XeroContact;
use Illuminate\Support\Facades\Log;

final readonly class SyncContactToXeroAction
{
    public function handle(Contact $contact, XeroConnection $connection): void
    {
        if (! XeroConnector::isConfigured()) {
            Log::warning('xero.deferred: XERO_CLIENT_ID not configured — SyncContactToXeroAction skipped', [
                'contact_id' => $contact->id,
                'xero_connection_id' => $connection->id,
            ]);

            return;
        }

        XeroContact::query()->updateOrCreate(
            [
                'contact_id' => $contact->id,
                'xero_connection_id' => $connection->id,
            ],
            [
                'xero_contact_id' => $contact->id.'-pending',
                'sync_status' => 'pending',
                'last_synced_at' => now(),
            ]
        );
    }
}
