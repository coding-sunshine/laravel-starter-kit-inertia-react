<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Flyer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Flyer>
 */
final class FlyerFactory extends Factory
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
            'lot_id' => null,
            'flyer_template_id' => null,
            'is_custom' => false,
            'created_by' => null,
            'legacy_id' => null,
        ];
    }
}
