<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AutomationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationRule>
 */
final class AutomationRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => 1,
            'name' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->sentence(),
            'event' => $this->faker->randomElement([
                'contact.stage_changed',
                'sale.status_changed',
                'task.created',
                'task.completed',
                'lead.captured',
            ]),
            'conditions' => [],
            'actions' => [],
            'is_active' => $this->faker->boolean(80),
            'run_count' => $this->faker->numberBetween(0, 100),
            'last_run_at' => $this->faker->optional()->dateTimeThisYear(),
        ];
    }
}
