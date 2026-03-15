<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Contact;
use App\Models\XeroConnection;
use App\Models\XeroContact;
use Illuminate\Database\Seeder;

final class XeroContactSeeder extends Seeder
{
    public function run(): void
    {
        if (XeroContact::query()->exists()) {
            return;
        }

        $connection = XeroConnection::query()->first();
        $contact = Contact::query()->first();

        if (! $connection || ! $contact) {
            return;
        }

        XeroContact::query()->create([
            'contact_id' => $contact->id,
            'xero_connection_id' => $connection->id,
            'xero_contact_id' => 'xero-contact-'.uniqid(),
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);
    }
}
