<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Siding>
 */
final class SidingFactory extends Factory
{
    protected $model = Siding::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = mb_strtoupper(Str::of($this->faker->unique()->lexify('???'))->toString());

        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->company().' Siding',
            'code' => $code,
            'location' => $this->faker->city(),
            'station_code' => mb_strtoupper(Str::of($this->faker->lexify('???'))->toString()),
            'is_active' => true,
        ];
    }
}
