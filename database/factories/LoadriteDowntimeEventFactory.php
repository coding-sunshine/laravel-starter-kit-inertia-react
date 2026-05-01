<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LoadriteDowntimeEvent;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LoadriteDowntimeEvent> */
final class LoadriteDowntimeEventFactory extends Factory
{
    protected $model = LoadriteDowntimeEvent::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-30 days', '-1 day');
        $duration = $this->faker->numberBetween(5, 180);

        return [
            'siding_id' => Siding::factory(),
            'downtime_id' => (string) $this->faker->unique()->numberBetween(1, 999999),
            'start_local_time' => $start,
            'end_local_time' => (clone $start)->modify("+{$duration} minutes"),
            'duration_minutes' => $duration,
            'reason_name' => $this->faker->randomElement(['Plant Stoppage', 'Maintenance', 'Weather', 'Power Outage']),
            'sub_reason_name' => null,
            'equipment_name' => $this->faker->randomElement(['Conveyor 1', 'Crusher A', null]),
            'raw_payload' => [],
        ];
    }
}
