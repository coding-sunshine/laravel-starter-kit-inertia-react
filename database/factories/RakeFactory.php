<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Indent;
use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Rake>
 */
final class RakeFactory extends Factory
{
    protected $model = Rake::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siding_id' => Siding::factory(),
            'indent_id' => null,
            'rake_number' => 'RAKE-'.mb_strtoupper(Str::of($this->faker->unique()->bothify('???-####'))->toString()),
            'rake_type' => null,
            'wagon_count' => null,
            'placement_time' => null,
            'dispatch_time' => null,
            'loaded_weight_mt' => null,
            'predicted_weight_mt' => null,
            'rr_expected_date' => null,
            'rr_actual_date' => null,
            'state' => null,
            'loading_start_time' => null,
            'loading_end_time' => null,
            'loading_free_minutes' => 180,
            'guard_start_time' => null,
            'guard_end_time' => null,
            'weighment_start_time' => null,
            'weighment_end_time' => null,
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }

    public function withSiding(Siding $siding): static
    {
        return $this->state(fn (): array => [
            'siding_id' => $siding->id,
        ]);
    }

    public function withIndent(?Indent $indent): static
    {
        return $this->state(fn (): array => [
            'indent_id' => $indent?->id,
        ]);
    }
}
