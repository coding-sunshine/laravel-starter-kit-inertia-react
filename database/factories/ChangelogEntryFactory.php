<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ChangelogType;
use App\Models\ChangelogEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChangelogEntry>
 */
final class ChangelogEntryFactory extends Factory
{
    protected $model = ChangelogEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraphs(2, true),
            'version' => $this->faker->optional(0.8)->numerify('#.#.#'),
            'type' => $this->faker->randomElement(ChangelogType::cases()),
            'is_published' => false,
            'released_at' => null,
        ];
    }

    public function published(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => true,
            'released_at' => now(),
        ]);
    }
}
