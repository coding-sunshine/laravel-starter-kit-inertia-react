<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Rake;
use App\Models\Wagon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Wagon>
 */
final class WagonFactory extends Factory
{
    protected $model = Wagon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rake_id' => Rake::factory(),
            'wagon_sequence' => null,
            'wagon_number' => 'WGN-'.mb_strtoupper(Str::of($this->faker->unique()->bothify('??####'))->toString()),
            'wagon_type' => null,
            'tare_weight_mt' => null,
            'pcc_weight_mt' => null,
            'is_unfit' => false,
            'state' => 'pending',
        ];
    }

    public function forRake(Rake $rake): static
    {
        return $this->state(fn (): array => [
            'rake_id' => $rake->id,
        ]);
    }
}
