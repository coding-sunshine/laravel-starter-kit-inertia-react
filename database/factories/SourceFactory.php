<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Source>
 */
final class SourceFactory extends Factory
{
    protected $model = Source::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => fake()->unique()->randomElement([
                'Website', 'Referral', 'Cold Call', 'Email Campaign',
                'Social Media', 'Trade Show', 'Direct Mail', 'Partner',
                'Advertisement', 'Walk-in', 'Webinar', 'Conference',
            ]),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
