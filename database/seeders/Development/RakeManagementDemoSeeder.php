<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Alert;
use App\Models\CoalStock;
use App\Models\GuardInspection;
use App\Models\Indent;
use App\Models\Loader;
use App\Models\Penalty;
use App\Models\PowerPlant;
use App\Models\PowerPlantReceipt;
use App\Models\Rake;
use App\Models\RrDocument;
use App\Models\RrPrediction;
use App\Models\Siding;
use App\Models\StockLedger;
use App\Models\Txr;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleArrival;
use App\Models\VehicleUnload;
use App\Models\Wagon;
use App\Models\Weighment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds full RRMCS demo data for superadmin/manager demos.
 * Safe to run on live for demo: uses DEMO-* prefixes for unique keys.
 *
 * Run after Essential seeders (sidings, users, vehicles, routes, loaders):
 *   php artisan db:seed --class=Database\\Seeders\\Development\\RakeManagementDemoSeeder
 */
final class RakeManagementDemoSeeder extends Seeder
{
    /**
     * Seeder dependencies (Essential seeders that must run first).
     *
     * @var array<string>
     */
    public array $dependencies = [
        'SidingSeeder',
        'UserSeeder',
        'VehicleSeeder',
        'RouteSeeder',
        'LoaderSeeder',
        'PowerPlantSeeder',
    ];

    private ?User $demoUser = null;

    public function run(): void
    {
        $this->demoUser = User::where('email', 'superadmin@rrmcs.local')->first()
            ?? User::first();

        if (! $this->demoUser) {
            $this->command?->warn('No user found. Run UserSeeder first.');

            return;
        }

        $sidings = Siding::whereIn('code', ['PKUR', 'DUMK', 'KURWA'])->get();
        if ($sidings->isEmpty()) {
            $sidings = Siding::all();
        }
        if ($sidings->isEmpty()) {
            $this->command?->warn('No sidings found. Run SidingSeeder first.');

            return;
        }

        DB::transaction(function () use ($sidings): void {
            foreach ($sidings as $siding) {
                $this->seedIndentsForSiding($siding);
            }
            foreach ($sidings as $siding) {
                $this->seedRakesAndRelatedForSiding($siding);
            }
            foreach ($sidings as $siding) {
                $this->seedVehicleArrivalsAndUnloadsForSiding($siding);
            }
            foreach ($sidings as $siding) {
                $this->seedStockLedgersForSiding($siding);
            }
            $this->seedAlertsForRakes($sidings);
            $this->seedPowerPlantReceipts();
        });
    }

    private function seedIndentsForSiding(Siding $siding): void
    {
        $base = 'DEMO-IND-'.$siding->code.'-';
        $states = ['pending', 'pending', 'allocated', 'allocated', 'completed'];
        $now = Carbon::now();

        for ($i = 1; $i <= 5; $i++) {
            Indent::firstOrCreate(
                ['indent_number' => $base.$i],
                [
                    'siding_id' => $siding->id,
                    'target_quantity_mt' => 2500 + ($i * 100),
                    'allocated_quantity_mt' => $states[$i - 1] === 'completed' ? 2600 : ($states[$i - 1] === 'allocated' ? 2500 : 0),
                    'state' => $states[$i - 1],
                    'indent_date' => $now->copy()->subDays(10 - $i),
                    'required_by_date' => $now->copy()->addDays($i),
                    'remarks' => 'Demo indent for '.$siding->name,
                    'created_by' => $this->demoUser->id,
                    'updated_by' => $this->demoUser->id,
                ]
            );
        }
    }

    private function seedRakesAndRelatedForSiding(Siding $siding): void
    {
        $loaders = Loader::where('siding_id', $siding->id)->get();
        $loader = $loaders->first();
        $base = 'DEMO-RK-'.$siding->code.'-';
        $now = Carbon::now();
        $states = [
            ['state' => 'pending', 'loading_start' => null, 'loading_end' => null, 'demurrage' => 0],
            ['state' => 'loading', 'loading_start' => $now->copy()->subMinutes(90), 'loading_end' => null, 'demurrage' => 0],
            ['state' => 'loaded', 'loading_start' => $now->copy()->subHours(3), 'loading_end' => $now->copy()->subMinutes(30), 'demurrage' => 0],
            ['state' => 'dispatched', 'loading_start' => $now->copy()->subHours(5), 'loading_end' => $now->copy()->subHours(2), 'demurrage' => 1],
        ];

        for ($r = 1; $r <= 4; $r++) {
            $cfg = $states[$r - 1];
            $rake = Rake::firstOrCreate(
                ['rake_number' => $base.$r],
                [
                    'siding_id' => $siding->id,
                    'rake_type' => 'BOBRN',
                    'wagon_count' => 58,
                    'loading_start_time' => $cfg['loading_start'],
                    'loading_end_time' => $cfg['loading_end'],
                    'loaded_weight_mt' => $cfg['loading_end'] ? 2900 + $r * 20 : null,
                    'predicted_weight_mt' => 2920,
                    'state' => $cfg['state'],
                    'free_time_minutes' => 180,
                    'demurrage_hours' => $cfg['demurrage'],
                    'demurrage_penalty_amount' => $cfg['demurrage'] * 15440,
                    'rr_expected_date' => $now->copy()->addDays(2),
                    'rr_actual_date' => null,
                    'created_by' => $this->demoUser->id,
                    'updated_by' => $this->demoUser->id,
                ]
            );

            $this->seedWagonsForRake($rake, $loader);
            $this->seedWeighmentForRake($rake);
            $this->seedGuardInspectionForRake($rake);
            $this->seedTxrForRake($rake);
            $this->seedRrPredictionForRake($rake);
            if ($r <= 2) {
                $this->seedRrDocumentForRake($rake, $r);
            }
            if ($r >= 3) {
                $this->seedPenaltiesForRake($rake, $r);
            }
        }
    }

    private function seedWagonsForRake(Rake $rake, ?Loader $loader): void
    {
        $count = 58;
        $shortRake = str_replace('DEMO-RK-', 'D', $rake->rake_number);
        $rakeBase = 'W-'.$shortRake.'-';

        for ($seq = 1; $seq <= $count; $seq++) {
            $wagonNum = $rakeBase.$seq;
            Wagon::firstOrCreate(
                ['wagon_number' => $wagonNum],
                [
                    'rake_id' => $rake->id,
                    'wagon_sequence' => $seq,
                    'wagon_type' => 'BOBRN',
                    'tare_weight_mt' => 22.5,
                    'loaded_weight_mt' => in_array($rake->state, ['loaded', 'dispatched'], true) ? 50.0 + (($seq % 10) * 0.5) : null,
                    'pcc_weight_mt' => 58.0,
                    'loader_recorded_qty_mt' => in_array($rake->state, ['loaded', 'dispatched'], true) ? 50.0 : null,
                    'weighment_qty_mt' => in_array($rake->state, ['loaded', 'dispatched'], true) ? 50.2 : null,
                    'is_unfit' => $seq === 10 && $rake->state !== 'pending',
                    'is_overloaded' => false,
                    'state' => $rake->state === 'pending' ? 'pending' : ($seq === 10 ? 'unfit' : 'loaded'),
                    'loader_id' => $loader?->id,
                ]
            );
        }
    }

    private function seedWeighmentForRake(Rake $rake): void
    {
        if (! in_array($rake->state, ['loaded', 'dispatched'], true)) {
            return;
        }
        $weighmentTime = $rake->loading_end_time?->copy()->addMinutes(15) ?? Carbon::now()->subHour();
        Weighment::firstOrCreate(
            [
                'rake_id' => $rake->id,
                'weighment_time' => $weighmentTime,
            ],
            [
                'weighment_time' => $weighmentTime,
                'total_weight_mt' => (float) ($rake->loaded_weight_mt ?? 2920),
                'average_wagon_weight_mt' => round((float) ($rake->loaded_weight_mt ?? 2920) / 58, 2),
                'weighment_status' => 'verified',
                'remarks' => 'Demo weighment',
                'created_by' => $this->demoUser->id,
            ]
        );
    }

    private function seedGuardInspectionForRake(Rake $rake): void
    {
        if (! in_array($rake->state, ['loaded', 'dispatched'], true)) {
            return;
        }
        GuardInspection::firstOrCreate(
            ['rake_id' => $rake->id],
            [
                'inspection_time' => $rake->loading_end_time ?? Carbon::now(),
                'is_approved' => true,
                'remarks' => 'Demo guard inspection',
                'created_by' => $this->demoUser->id,
            ]
        );
    }

    private function seedTxrForRake(Rake $rake): void
    {
        Txr::firstOrCreate(
            ['rake_id' => $rake->id],
            [
                'inspection_time' => $rake->loading_start_time ?? Carbon::now()->subHour(),
                'state' => 'approved',
                'unfit_wagons_count' => $rake->state !== 'pending' ? 1 : 0,
                'unfit_wagon_numbers' => $rake->state !== 'pending' ? '["W-'.str_replace('DEMO-RK-', 'D', $rake->rake_number).'-10"]' : null,
                'remarks' => 'Demo TXR',
                'created_by' => $this->demoUser->id,
            ]
        );
    }

    private function seedRrPredictionForRake(Rake $rake): void
    {
        RrPrediction::firstOrCreate(
            ['rake_id' => $rake->id],
            [
                'predicted_weight_mt' => $rake->predicted_weight_mt ?? 2920,
                'predicted_rr_date' => Carbon::now()->addDays(2),
                'prediction_confidence' => 'high',
                'prediction_status' => 'pending',
                'variance_percent' => null,
            ]
        );
    }

    private function seedRrDocumentForRake(Rake $rake, int $r): void
    {
        $rrNum = 'DEMO-RR-'.$rake->rake_number;
        RrDocument::firstOrCreate(
            ['rr_number' => $rrNum],
            [
                'rake_id' => $rake->id,
                'rr_received_date' => Carbon::now()->subDays($r),
                'rr_weight_mt' => 2915,
                'rr_details' => '{"demo": true}',
                'document_status' => 'verified',
                'has_discrepancy' => false,
                'created_by' => $this->demoUser->id,
                'updated_by' => $this->demoUser->id,
            ]
        );
    }

    private function seedPenaltiesForRake(Rake $rake, int $r): void
    {
        $rate = (float) config('rrmcs.demurrage_rate_per_mt_hour', 50);
        $weightMt = (float) ($rake->loaded_weight_mt ?? 2920);
        $freeMinutes = $rake->free_time_minutes !== null && $rake->free_time_minutes !== '' ? (float) $rake->free_time_minutes : 180.0;
        $freeHours = round($freeMinutes / 60, 2);

        $types = ['DEM', 'POL1'];
        $amounts = [15440, 25000];
        foreach ([0, 1] as $idx) {
            $amount = $amounts[$idx];
            $isDemurrage = $types[$idx] === 'DEM';

            $breakdown = null;
            if ($isDemurrage && $rate > 0 && $weightMt > 0) {
                $demurrageHours = round($amount / ($weightMt * $rate), 2);
                $dwellHours = round($freeHours + $demurrageHours, 2);
                $breakdown = [
                    'formula' => 'demurrage_hours × weight_mt × rate_per_mt_hour',
                    'demurrage_hours' => $demurrageHours,
                    'weight_mt' => round($weightMt, 2),
                    'rate_per_mt_hour' => $rate,
                    'free_hours' => $freeHours,
                    'dwell_hours' => $dwellHours,
                ];
            }

            $description = $isDemurrage
                ? sprintf(
                    'Demurrage: %s h × %s MT × ₹%s/MT/h = ₹%s',
                    number_format($breakdown['demurrage_hours'] ?? 0, 1),
                    number_format($weightMt, 1),
                    number_format($rate, 0),
                    number_format($amount, 2)
                )
                : 'Demo '.$types[$idx].' penalty';

            Penalty::updateOrCreate(
                [
                    'rake_id' => $rake->id,
                    'penalty_type' => $types[$idx],
                    'penalty_date' => Carbon::now()->subDays($r),
                ],
                [
                    'penalty_amount' => $amount,
                    'penalty_status' => $idx === 0 ? 'incurred' : 'pending',
                    'description' => $description,
                    'remediation_notes' => null,
                    'calculation_breakdown' => $breakdown,
                ]
            );
        }
    }

    private function seedVehicleArrivalsAndUnloadsForSiding(Siding $siding): void
    {
        $vehicles = Vehicle::limit(4)->get();
        $indents = Indent::where('siding_id', $siding->id)->whereIn('state', ['allocated', 'completed'])->limit(2)->get();
        $now = Carbon::now();

        foreach ($vehicles->take(3) as $idx => $vehicle) {
            $arrivedAt = $now->copy()->subHours(6 - $idx);
            $arrival = VehicleArrival::firstOrCreate(
                [
                    'siding_id' => $siding->id,
                    'vehicle_id' => $vehicle->id,
                    'arrived_at' => $arrivedAt,
                ],
                [
                    'indent_id' => $indents->get($idx % 2)?->id,
                    'status' => $idx === 0 ? 'completed' : ($idx === 1 ? 'unloading' : 'pending'),
                    'shift' => ['morning', 'evening', 'night'][$idx % 3],
                    'unloading_started_at' => $idx <= 1 ? $arrivedAt->copy()->addMinutes(10) : null,
                    'unloading_completed_at' => $idx === 0 ? $arrivedAt->copy()->addMinutes(45) : null,
                    'gross_weight' => 38.5,
                    'tare_weight' => (float) $vehicle->tare_weight_mt,
                    'net_weight' => 38.5 - (float) $vehicle->tare_weight_mt,
                    'unloaded_quantity' => $idx === 0 ? 30.2 : null,
                    'notes' => 'Demo arrival',
                    'created_by' => $this->demoUser->id,
                    'updated_by' => $this->demoUser->id,
                ]
            );

            if ($idx === 0) {
                VehicleUnload::firstOrCreate(
                    [
                        'siding_id' => $siding->id,
                        'vehicle_id' => $vehicle->id,
                        'arrival_time' => $arrivedAt,
                    ],
                    [
                        'jimms_challan_number' => 'DEMO-CH-'.$siding->code.'-1',
                        'shift' => 'morning',
                        'unload_start_time' => $arrivedAt->copy()->addMinutes(10),
                        'unload_end_time' => $arrivedAt->copy()->addMinutes(45),
                        'mine_weight_mt' => 30.0,
                        'weighment_weight_mt' => 30.2,
                        'variance_mt' => 0.2,
                        'state' => 'completed',
                        'remarks' => 'Demo unload',
                        'created_by' => $this->demoUser->id,
                        'updated_by' => $this->demoUser->id,
                    ]
                );
            }
        }
    }

    private function seedStockLedgersForSiding(Siding $siding): void
    {
        $arrivals = VehicleArrival::where('siding_id', $siding->id)->where('status', 'completed')->limit(2)->get();
        $opening = 500.0;

        foreach ($arrivals as $idx => $arrival) {
            $qty = (float) $arrival->unloaded_quantity;
            if ($qty <= 0) {
                continue;
            }
            $closing = $opening + $qty;
            StockLedger::firstOrCreate(
                [
                    'siding_id' => $siding->id,
                    'vehicle_arrival_id' => $arrival->id,
                    'transaction_type' => 'receipt',
                ],
                [
                    'quantity_mt' => $qty,
                    'opening_balance_mt' => $opening,
                    'closing_balance_mt' => $closing,
                    'reference_number' => 'DEMO-RCP-'.$siding->code.'-'.($idx + 1),
                    'remarks' => 'Demo receipt from vehicle arrival',
                    'created_by' => $this->demoUser->id,
                ]
            );
            $opening = $closing;
        }

        CoalStock::firstOrCreate(
            [
                'siding_id' => $siding->id,
                'as_of_date' => Carbon::today(),
            ],
            [
                'opening_balance_mt' => 500,
                'receipt_quantity_mt' => 60.4,
                'dispatch_quantity_mt' => 0,
                'closing_balance_mt' => 560.4,
                'remarks' => 'Demo coal stock snapshot',
            ]
        );
    }

    private function seedAlertsForRakes(\Illuminate\Support\Collection $sidings): void
    {
        $sidingIds = $sidings->pluck('id')->all();
        $rakes = Rake::whereIn('siding_id', $sidingIds)
            ->whereIn('state', ['loading', 'loaded'])
            ->get();

        foreach ($rakes as $rake) {
            if ($rake->state === 'loading') {
                Alert::firstOrCreate(
                    [
                        'rake_id' => $rake->id,
                        'type' => 'demurrage_60',
                        'status' => 'active',
                    ],
                    [
                        'siding_id' => $rake->siding_id,
                        'user_id' => $this->demoUser->id,
                        'title' => 'Demurrage warning: '.$rake->rake_number,
                        'body' => 'Free time under 60 minutes. Complete loading soon.',
                        'severity' => 'warning',
                        'created_at' => now(),
                    ]
                );
            }
        }

        $overloadRake = Rake::whereIn('siding_id', $sidingIds)->where('state', 'loaded')->first();
        if ($overloadRake) {
            Alert::firstOrCreate(
                [
                    'rake_id' => $overloadRake->id,
                    'type' => 'overload',
                    'status' => 'active',
                ],
                [
                    'siding_id' => $overloadRake->siding_id,
                    'user_id' => $this->demoUser->id,
                    'title' => 'Overload flag: '.$overloadRake->rake_number,
                    'body' => 'Loader vs weighment variance exceeds threshold.',
                    'severity' => 'critical',
                    'created_at' => now(),
                ]
            );
        }

        Alert::firstOrCreate(
            [
                'siding_id' => $sidings->first()->id,
                'type' => 'stock_low',
                'status' => 'active',
            ],
            [
                'user_id' => $this->demoUser->id,
                'title' => 'Low stock alert',
                'body' => 'Siding stock below recommended level.',
                'severity' => 'info',
                'created_at' => now(),
            ]
        );
    }

    private function seedPowerPlantReceipts(): void
    {
        $plants = PowerPlant::where('is_active', true)->get();
        if ($plants->isEmpty()) {
            return;
        }

        $rakesWithRr = Rake::whereHas('rrDocuments')->with('rrDocuments')->get();
        foreach ($rakesWithRr->take(3) as $idx => $rake) {
            $rr = $rake->rrDocuments()->orderByDesc('rr_received_date')->first();
            $plant = $plants->get($idx % $plants->count());
            if (! $plant || ! $rr) {
                continue;
            }
            PowerPlantReceipt::firstOrCreate(
                [
                    'rake_id' => $rake->id,
                    'power_plant_id' => $plant->id,
                ],
                [
                    'receipt_date' => $rr->rr_received_date,
                    'weight_mt' => (float) ($rr->rr_weight_mt ?? 2915),
                    'rr_reference' => $rr->rr_number,
                    'variance_mt' => 0,
                    'variance_pct' => 0,
                    'status' => 'received',
                    'created_by' => $this->demoUser->id,
                ]
            );
        }
    }
}
