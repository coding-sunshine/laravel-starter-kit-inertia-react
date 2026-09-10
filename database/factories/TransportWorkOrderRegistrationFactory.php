<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Siding;
use App\Models\TransportWorkOrderRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransportWorkOrderRegistration>
 */
final class TransportWorkOrderRegistrationFactory extends Factory
{
    protected $model = TransportWorkOrderRegistration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siding_id' => null,
            'work_order_no_1' => null,
            'work_order_no_2' => null,
            'reference_no' => null,
            'work_order_date' => null,
            'transporter_name' => fake()->company(),
            'trade_name' => null,
            'legal_name_of_business' => fake()->company().' Private Limited',
            'pan_card' => null,
            'gst_no' => null,
            'status' => null,
            'email' => fake()->unique()->safeEmail(),
            'vendor_code' => null,
            'mobile_1' => null,
            'mobile_2' => null,
            'address' => fake()->address(),
            'gramin_or_non_gramin' => fake()->randomElement(TransportWorkOrderRegistration::GRAMIN_OR_NON_GRAMIN_VALUES),
            'is_active' => true,
        ];
    }

    public function forSiding(Siding $siding): self
    {
        return $this->state(fn (): array => [
            'siding_id' => $siding->id,
        ]);
    }

    public function withWorkOrderNo2(string $workOrderNo2): self
    {
        return $this->state(fn (): array => [
            'work_order_no_2' => $workOrderNo2,
        ]);
    }
}
