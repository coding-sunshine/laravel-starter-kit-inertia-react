<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Mention;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mention>
 */
final class MentionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mentioned_user_id' => User::factory(),
            'mentioned_by_user_id' => User::factory(),
            'context' => $this->faker->sentence(),
            'notified_at' => $this->faker->optional()->dateTimeThisYear(),
            'organization_id' => 1,
        ];
    }
}
