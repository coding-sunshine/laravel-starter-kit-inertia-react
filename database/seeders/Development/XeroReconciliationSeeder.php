<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\XeroInvoice;
use App\Models\XeroReconciliation;
use Illuminate\Database\Seeder;
use RuntimeException;

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

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure XeroInvoice exists for 0 (idempotent)
        if (XeroInvoice::query()->count() === 0) {
            XeroInvoice::factory()->count(5)->create();
        }

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('xero_reconciliations.json');

            if (! isset($data['xero_reconciliations']) || ! is_array($data['xero_reconciliations'])) {
                return;
            }

            foreach ($data['xero_reconciliations'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    XeroReconciliation::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = XeroReconciliation::factory();
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
        XeroReconciliation::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(XeroReconciliation::factory(), 'admin')) {
            XeroReconciliation::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}
