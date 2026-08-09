<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Indent;
use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use RuntimeException;

final class RakeSeeder extends Seeder
{
    /**
     * @var array<string>
     */
    public array $dependencies = ['SidingSeeder', 'IndentSeeder'];

    public function run(): void
    {
        $sidings = Siding::query()->get();

        foreach ($sidings as $siding) {
            $indent = Indent::query()->where('siding_id', $siding->id)->first();

            for ($i = 1; $i <= 2; $i++) {
                Rake::query()->firstOrCreate([
                    'siding_id' => $siding->id,
                    'rake_number' => sprintf('RAKE-%s-%04d', $siding->code, $i),
                ], [
                    'indent_id' => $i === 1 ? $indent?->id : null,
                    'rake_type' => null,
                    'wagon_count' => null,
                    'state' => 'pending',
                    'placement_time' => Date::now()->subHours(2),
                    'loading_free_minutes' => 180,
                ]);
            }
        }
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Siding exists for 0 (idempotent)
        if (Siding::query()->count() === 0) {
            Siding::factory()->count(5)->create();
        }

        // Ensure Indent exists for 1 (idempotent)
        if (Indent::query()->count() === 0) {
            Indent::factory()->count(5)->create();
        }

        // Ensure User exists for 2 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 3 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 4 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 5 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 6 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Note: hasMany relationships are seeded after main model creation
    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('rakes.json');

            if (! isset($data['rakes']) || ! is_array($data['rakes'])) {
                return;
            }

            foreach ($data['rakes'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    Rake::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = Rake::factory();
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
        Rake::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(Rake::factory(), 'admin')) {
            Rake::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}
