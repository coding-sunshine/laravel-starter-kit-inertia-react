<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\MatchScore;
use Illuminate\Database\Seeder;
use RuntimeException;

final class MatchScoreSeeder extends Seeder
{
    public function run(): void
    {
        if (MatchScore::query()->exists()) {
            return;
        }
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Contact exists for 0 (idempotent)
        if (\App\Models\Contact::query()->count() === 0) {
            \App\Models\Contact::factory()->count(5)->create();
        }

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('match_scores.json');

            if (! isset($data['match_scores']) || ! is_array($data['match_scores'])) {
                return;
            }

            foreach ($data['match_scores'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    MatchScore::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = MatchScore::factory();
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
        MatchScore::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(MatchScore::factory(), 'admin')) {
            MatchScore::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}
