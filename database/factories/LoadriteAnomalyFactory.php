<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LoadriteAnomaly;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoadriteAnomaly>
 */
final class LoadriteAnomalyFactory extends Factory
{
    protected $model = LoadriteAnomaly::class;

    public function definition(): array
    {
        return [
            'siding_id' => Siding::factory(),
            'loadrite_event_id' => null,
            'kind' => fake()->randomElement([
                'wagon_type_unmappable',
                'operator_unmappable',
                'bogus_timestamp',
                'rake_serial_missing',
            ]),
            'raw_value' => fake()->optional()->word(),
            'context' => ['source' => 'test'],
            'status' => 'open',
            'resolved_at' => null,
            'resolved_by' => null,
        ];
    }

    public function wagontTypeUnmappable(): self
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => 'wagon_type_unmappable',
            'raw_value' => fake()->lexify('??-?'),
        ]);
    }

    public function bogusTimestamp(): self
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => 'bogus_timestamp',
            'raw_value' => now()->addYears(10)->toDateTimeString(),
        ]);
    }

    public function resolved(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function ignored(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'ignored',
        ]);
    }
}
