<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Siding;
use App\Models\SidingPreIndentReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SidingPreIndentReport>
 */
final class SidingPreIndentReportFactory extends Factory
{
    protected $model = SidingPreIndentReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siding_id' => Siding::factory(),
            'report_date' => fake()->date(),
            'total_indent_raised' => fake()->numberBetween(0, 20),
            'indent_available' => fake()->numberBetween(0, 20),
            'loading_status_text' => fake()->optional()->sentence(),
            'indent_details_text' => fake()->optional()->paragraph(),
        ];
    }
}
