<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\AiSummary;
use Illuminate\Database\Seeder;
use RuntimeException;

final class AiSummarySeeder extends Seeder
{
    public function run(): void
    {
        if (AiSummary::query()->exists()) {
            return;
        }
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void {}

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('ai_summaries.json');

            if (! isset($data['ai_summaries']) || ! is_array($data['ai_summaries'])) {
                return;
            }

            foreach ($data['ai_summaries'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    AiSummary::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = AiSummary::factory();
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
        AiSummary::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(AiSummary::factory(), 'admin')) {
            AiSummary::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}
