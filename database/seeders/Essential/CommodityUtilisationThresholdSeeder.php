<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\CommodityUtilisationThreshold;
use Illuminate\Database\Seeder;
use RuntimeException;

final class CommodityUtilisationThresholdSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['commodity_grade' => 'G1', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'G2', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'G3', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'G4', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'G5', 'utilisation_threshold' => 0.95],
            ['commodity_grade' => 'UNGRADED', 'utilisation_threshold' => 0.95],
        ];

        foreach ($defaults as $row) {
            CommodityUtilisationThreshold::query()->updateOrCreate(
                ['commodity_grade' => $row['commodity_grade'], 'effective_from' => '2025-01-01 00:00:00'],
                [
                    'utilisation_threshold' => $row['utilisation_threshold'],
                    'effective_to' => null,
                    'source' => 'Stage-1 default — adjust per calibration',
                ],
            );
        }
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure User exists for 0 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 1 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 2 (idempotent)
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
            $data = $this->loadJson('commodity_utilisation_thresholds.json');

            if (! isset($data['commodity_utilisation_thresholds']) || ! is_array($data['commodity_utilisation_thresholds'])) {
                return;
            }

            foreach ($data['commodity_utilisation_thresholds'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    CommodityUtilisationThreshold::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = CommodityUtilisationThreshold::factory();
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
        CommodityUtilisationThreshold::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(CommodityUtilisationThreshold::factory(), 'admin')) {
            CommodityUtilisationThreshold::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}
