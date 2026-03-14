<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PinnedNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PinnedNote>
 */
final class PinnedNoteFactory extends Factory
{
    protected $model = PinnedNote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => fake()->paragraph(),
            'role_visibility' => null,
            'is_active' => true,
            'order' => fake()->numberBetween(0, 10),
        ];
    }
}
