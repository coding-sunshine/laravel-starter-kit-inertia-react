<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Rake;
use App\Models\RrDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RrDocument>
 */
final class RrDocumentFactory extends Factory
{
    protected $model = RrDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rake_id' => Rake::factory(),
            'rr_number' => 'RR-'.$this->faker->unique()->numerify('##########'),
            'rr_received_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'rr_weight_mt' => $this->faker->randomFloat(2, 1000, 4000),
            'distance_km' => $this->faker->randomFloat(2, 50, 800),
            'freight_total' => $this->faker->randomFloat(2, 50000, 500000),
            'document_status' => 'parsed',
            'data_source' => 'historical_rr_snapshot',
        ];
    }
}
