<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Rake;
use App\Models\Wagon;
use App\Models\WagonLoading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WagonLoading>
 */
final class WagonLoadingFactory extends Factory
{
    protected $model = WagonLoading::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rake_id' => Rake::factory(),
            'wagon_id' => Wagon::factory(),
            'loader_id' => null,
            'loader_operator_name' => null,
            'cc_capacity_mt' => null,
            'loaded_quantity_mt' => null,
            'loading_time' => null,
            'remarks' => null,
            'loadrite_weight_mt' => null,
            'weight_source' => 'manual',
            'loadrite_last_synced_at' => null,
            'loadrite_override' => false,
        ];
    }
}
