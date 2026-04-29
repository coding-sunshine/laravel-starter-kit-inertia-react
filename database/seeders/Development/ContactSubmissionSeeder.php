<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\ContactSubmission;
use Illuminate\Database\Seeder;
use RuntimeException;

final class ContactSubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds (idempotent).
     */
    public function run(): void
    {
        if (ContactSubmission::query()->exists()) {
            return;
        }

        ContactSubmission::factory()
            ->count(5)
            ->create();
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Organization exists for 0 (idempotent)
        if (\App\Models\Organization::query()->count() === 0) {
            \App\Models\Organization::factory()->count(5)->create();
        }

        // Ensure User exists for 1 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 2 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 3 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('contact_submissions.json');

            if (! isset($data['contact_submissions']) || ! is_array($data['contact_submissions'])) {
                return;
            }

            foreach ($data['contact_submissions'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (isset($itemData['email']) && ! empty($itemData['email'])) {
                    ContactSubmission::query()->updateOrCreate(
                        ['email' => $itemData['email']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = ContactSubmission::factory();
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
        ContactSubmission::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(ContactSubmission::factory(), 'admin')) {
            ContactSubmission::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}
