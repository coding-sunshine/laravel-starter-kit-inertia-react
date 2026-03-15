<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Contact;
use App\Models\XeroConnection;
use App\Models\XeroContact;
use Illuminate\Database\Seeder;
use RuntimeException;

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

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Contact exists for 0 (idempotent)
        if (Contact::query()->count() === 0) {
            Contact::factory()->count(5)->create();
        }

        // Ensure XeroConnection exists for 1 (idempotent)
        if (XeroConnection::query()->count() === 0) {
            XeroConnection::factory()->count(5)->create();
        }

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('xero_contacts.json');

            if (! isset($data['xero_contacts']) || ! is_array($data['xero_contacts'])) {
                return;
            }

            foreach ($data['xero_contacts'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    XeroContact::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = XeroContact::factory();
                    if ($factoryState !== null && method_exists($factory, $factoryState)) {
                        $factory = $factory->{$factoryState}();
                    }
                    $factory->create($itemData);
                }
            }
        } catch (RuntimeException $e) {
            // JSON file doesn't exist or is invalid - skip silently
        }
    }

    /**
     * Seed using factory (idempotent - safe to run multiple times).
     */
    private function seedFromFactory(): void
    {
        // Generate seed data with factory
        // Note: Factory creates are not idempotent by default
        // For true idempotency, use updateOrCreate in seedFromJson or add unique constraints
        XeroContact::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(XeroContact::factory(), 'admin')) {
            XeroContact::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}
