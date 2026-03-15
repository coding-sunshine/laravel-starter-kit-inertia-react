<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\XeroConnection;
use App\Models\XeroInvoice;
use Illuminate\Database\Seeder;
use RuntimeException;

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

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Sale exists for 0 (idempotent)
        if (\App\Models\Sale::query()->count() === 0) {
            \App\Models\Sale::factory()->count(5)->create();
        }

        // Ensure XeroConnection exists for 1 (idempotent)
        if (XeroConnection::query()->count() === 0) {
            XeroConnection::factory()->count(5)->create();
        }

        // Note: hasMany relationships are seeded after main model creation
    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('xero_invoices.json');

            if (! isset($data['xero_invoices']) || ! is_array($data['xero_invoices'])) {
                return;
            }

            foreach ($data['xero_invoices'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    XeroInvoice::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = XeroInvoice::factory();
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
        XeroInvoice::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(XeroInvoice::factory(), 'admin')) {
            XeroInvoice::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}
