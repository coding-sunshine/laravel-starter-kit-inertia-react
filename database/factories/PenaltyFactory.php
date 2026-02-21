<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Penalty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penalty>
 */
final class PenaltyFactory extends Factory
{
    /**
     * Realistic root cause descriptions grouped by category.
     *
     * @var array<string, array<string>>
     */
    private const ROOT_CAUSES = [
        'Equipment Failure' => [
            'Equipment breakdown: payloader hydraulic failure during loading',
            'Equipment failure: weighbridge sensor malfunction caused delays',
            'Equipment breakdown: conveyor belt snapped mid-loading',
            'Equipment failure: tippler mechanism jammed during unloading',
            'Equipment breakdown: locomotive coupling failure at siding',
        ],
        'Labour Shortage' => [
            'Labour shortage: insufficient loading crew on night shift',
            'Labour shortage: absenteeism due to local festival',
            'Labour shortage: contract workers unavailable at short notice',
            'Labour unavailability: shift changeover gap caused idle time',
        ],
        'Weather/Environmental' => [
            'Weather delay: heavy rainfall halted loading operations',
            'Weather delay: fog reduced visibility below safe threshold',
            'Environmental: waterlogging on siding approach track',
            'Weather delay: cyclone warning forced operations shutdown',
        ],
        'Communication Gap' => [
            'Communication gap: rake placement notice received late',
            'Communication gap: incorrect wagon count communicated by railway',
            'Communication gap: siding not informed of rake diversion',
            'Miscommunication: wrong rake number on placement memo',
        ],
        'Scheduling Error' => [
            'Scheduling error: double booking of loading slot',
            'Scheduling error: rake arrived before indent was confirmed',
            'Scheduling error: loader not scheduled for night shift loading',
            'Scheduling conflict: two rakes placed simultaneously',
        ],
        'Documentation Delay' => [
            'Documentation delay: RR processing delayed by missing paperwork',
            'Documentation delay: weighment slip discrepancy held up dispatch',
            'Documentation delay: TXR inspection report pending approval',
            'Paperwork delay: guard brake certificate not issued on time',
        ],
        'Infrastructure Issue' => [
            'Infrastructure issue: track section under maintenance',
            'Infrastructure issue: power outage at weighbridge location',
            'Infrastructure issue: siding approach road damaged by trucks',
            'Infrastructure issue: signal failure at entry point',
        ],
        'Operational Delay' => [
            'Operational delay: slow loading rate due to coal quality issues',
            'Operational delay: wagon shunting took longer than expected',
            'Operational delay: previous rake clearance delayed placement',
            'Operational delay: overloaded wagons required re-adjustment',
            'Operational delay: quality inspection rejected initial load',
        ],
    ];

    /**
     * Maps root cause categories to their most likely responsible parties.
     *
     * @var array<string, string>
     */
    private const CAUSE_TO_PARTY = [
        'Equipment Failure' => 'siding',
        'Labour Shortage' => 'siding',
        'Weather/Environmental' => 'other',
        'Communication Gap' => 'railway',
        'Scheduling Error' => 'siding',
        'Documentation Delay' => 'transporter',
        'Infrastructure Issue' => 'railway',
        'Operational Delay' => 'plant',
    ];

    protected $model = Penalty::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rootCause = fake()->optional(0.7)->passthrough($this->randomRootCause());

        return [
            'rake_id' => fake()->numberBetween(1, 100),
            'penalty_type' => fake()->randomElement(['DEM', 'POL1', 'POLA', 'PLO', 'ULC', 'SPL', 'WMC', 'MCF']),
            'penalty_amount' => fake()->randomFloat(2, 500, 100000),
            'penalty_status' => fake()->randomElement(['pending', 'incurred', 'waived', 'disputed']),
            'responsible_party' => fake()->optional(0.7)->randomElement(['railway', 'siding', 'transporter', 'plant', 'other']),
            'root_cause' => $rootCause,
            'description' => fake()->optional(0.6)->sentence(),
            'remediation_notes' => null,
            'penalty_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'calculation_breakdown' => null,
        ];
    }

    public function demurrage(): self
    {
        return $this->state(fn (): array => [
            'penalty_type' => 'DEM',
            'calculation_breakdown' => [
                'formula' => 'demurrage_hours × weight_mt × rate_per_mt_hour',
                'demurrage_hours' => $hours = fake()->randomFloat(1, 0.5, 24),
                'weight_mt' => $weight = fake()->randomFloat(0, 500, 3000),
                'rate_per_mt_hour' => 50,
                'free_hours' => 3.0,
                'dwell_hours' => $hours + 3.0,
            ],
        ]);
    }

    public function disputed(): self
    {
        return $this->state(fn (): array => [
            'penalty_status' => 'disputed',
            'disputed_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'dispute_reason' => fake()->sentence(),
        ]);
    }

    public function resolved(): self
    {
        return $this->state(fn (): array => [
            'penalty_status' => 'waived',
            'disputed_at' => fake()->dateTimeBetween('-60 days', '-30 days'),
            'dispute_reason' => fake()->sentence(),
            'resolved_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'resolution_notes' => fake()->sentence(),
        ]);
    }

    public function withResponsibility(): self
    {
        return $this->state(function (): array {
            $category = fake()->randomElement(array_keys(self::ROOT_CAUSES));

            return [
                'responsible_party' => self::CAUSE_TO_PARTY[$category],
                'root_cause' => fake()->randomElement(self::ROOT_CAUSES[$category]),
            ];
        });
    }

    private function randomRootCause(): string
    {
        $category = fake()->randomElement(array_keys(self::ROOT_CAUSES));

        return fake()->randomElement(self::ROOT_CAUSES[$category]);
    }
}
