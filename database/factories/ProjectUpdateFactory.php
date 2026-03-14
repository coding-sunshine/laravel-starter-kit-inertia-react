<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProjectUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectUpdate>
 */
final class ProjectUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => null,
            'content' => $this->faker->paragraph(),
            'user_id' => null,
            'legacy_id' => null,
        ];
    }
}
