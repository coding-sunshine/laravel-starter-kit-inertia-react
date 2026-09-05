<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Indent;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Indent>
 */
final class IndentFactory extends Factory
{
    protected $model = Indent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siding_id' => Siding::factory(),
            'indent_number' => null,
            'demanded_stock' => null,
            'total_units' => null,
            'target_quantity_mt' => null,
            'allocated_quantity_mt' => null,
            'available_stock_mt' => null,
            'indent_date' => null,
            'indent_time' => null,
            'expected_loading_date' => null,
            'required_by_date' => null,
            'railway_reference_no' => null,
            'e_demand_reference_id' => null,
            'fnr_number' => null,
            'state' => null,
            'remarks' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
