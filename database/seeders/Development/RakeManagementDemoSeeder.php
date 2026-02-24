<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Alert;
use App\Models\CoalStock;
use App\Models\GuardInspection;
use App\Models\Indent;
use App\Models\Loader;
use App\Models\LoaderPerformance;
use App\Models\Penalty;
use App\Models\PowerPlant;
use App\Models\PowerPlantReceipt;
use App\Models\Rake;
use App\Models\RakeLoad;
use App\Models\RrDocument;
use App\Models\RrPrediction;
use App\Models\Siding;
use App\Models\SidingPerformance;
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
        $this->demoUser = User::query()->where('email', 'superadmin@rrmcs.local')->first()
            ?? User::query()->first();

        if (! $this->demoUser instanceof User) {
            $this->command?->warn('No user found. Run UserSeeder first.');

            return;
        }

        $sidings = Siding::query()->whereIn('code', ['PKUR', 'DUMK', 'KURWA'])->get();
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
            $this->seedLoaderPerformanceForSidings($sidings);
            $this->seedSidingPerformanceForSidings($sidings);
            $this->seedPowerPlantReceipts();
        });
    }

    private function seedIndentsForSiding(Siding $siding): void
    {
        $base = 'DEMO-IND-'.$siding->code.'-';
        $indentStates = ['pending', 'pending', 'allocated', 'allocated', 'completed'];
        $now = \Illuminate\Support\Facades\Date::now();

        for ($i = 1; $i <= 5; $i++) {
            Indent::query()->firstOrCreate(['indent_number' => $base.$i], [
                'siding_id' => $siding->id,
                'target_quantity_mt' => 2500 + ($i * 100),
                'allocated_quantity_mt' => $indentStates[$i - 1] === 'completed' ? 2600 : ($indentStates[$i - 1] === 'allocated' ? 2500 : 0),
                'state' => $indentStates[$i - 1],
                'indent_date' => $now->copy()->subDays(10 - $i),
                'required_by_date' => $now->copy()->addDays($i),
                'remarks' => 'Demo indent for '.$siding->name,
                'created_by' => $this->demoUser->id,
                'updated_by' => $this->demoUser->id,
            ]);
        }
    }

    private function seedRakesAndRelatedForSiding(Siding $siding): void
    {
        $loaders = Loader::query()->where('siding_id', $siding->id)->get();
        $loader = $loaders->first();
        $indents = Indent::query()->where('siding_id', $siding->id)->whereIn('state', ['allocated', 'completed'])->get();
        $base = 'DEMO-RK-'.$siding->code.'-';
        $now = \Illuminate\Support\Facades\Date::now();
        $rakeStates = [
            1 => ['pending', 'allocated'],
            2 => ['loading', 'loaded', 'dispatched'],
            3 => ['completed'],
            4 => ['pending', 'allocated'],
            5 => ['pending', 'allocated'],
        ];

        for ($r = 1; $r <= 4; $r++) {
            // Explicitly define states to avoid array key issues
            $rakeConfig = null;
            if ($r === 1) {
                $loadingStart = $now->copy()->subHours(6);
                $loadingEnd = $now->copy()->subHours(2);
                $rakeState = 'pending';
                $rakeAllocated = 'allocated';
            } elseif ($r === 2) {
                $loadingStart = $now->copy()->subHours(6);
                $loadingEnd = $now->copy()->subHours(2);
                $rakeState = 'loading';
                $rakeAllocated = 'loaded';
            } elseif ($r === 3) {
                $loadingStart = $now->copy()->subHours(6);
                $loadingEnd = $now->copy()->subHours(2);
                $rakeState = 'completed';
                $rakeAllocated = null;
            } elseif ($r === 4) {
                $loadingStart = $now->copy()->subHours(6);
                $loadingEnd = $now->copy()->subHours(2);
                $rakeState = 'pending';
                $rakeAllocated = 'allocated';
            } else {
                $loadingStart = $now->copy()->subHours(6);
                $loadingEnd = $now->copy()->subHours(2);
                $rakeState = 'pending';
                $rakeAllocated = 'allocated';
            }

            $rake = Rake::query()->firstOrCreate(['rake_number' => $base.$r], [
                'siding_id' => $siding->id,
                'indent_id' => ($r - 1) < $indents->count() ? $indents->get($r - 1)?->id : null, // Assign indent for 1:1 relationship
                'rake_type' => 'BOBRN',
                'wagon_count' => 58,
                'loaded_weight_mt' => $loadingEnd instanceof Carbon ? 2900 + $r * 20 : null,
                'predicted_weight_mt' => 2920,
                'state' => $rakeState,
                'free_time_minutes' => 180,
                'placement_time' => $loadingStart,
                'dispatch_time' => $rakeState === 'completed' ? $loadingEnd : null,
                'rr_expected_date' => $now->copy()->addDays(2),
                'rr_actual_date' => null,
                'created_by' => $this->demoUser->id,
                'updated_by' => $this->demoUser->id,
            ]);

            $this->seedWagonsForRake($rake, $loader);
            $this->seedRakeLoadForRake($rake);
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
            Wagon::query()->firstOrCreate(['wagon_number' => $wagonNum], [
                'rake_id' => $rake->id,
                'wagon_sequence' => $seq,
                'wagon_type' => 'BOBRN',
                'tare_weight_mt' => 22.5,
                'loaded_weight_mt' => in_array($rake->state, ['loaded', 'dispatched'], true) ? 50.0 + (($seq % 10) * 0.5) : null,
                'pcc_weight_mt' => 58.0,
                'loader_recorded_qty_mt' => in_array($rake->state, ['loaded', 'dispatched'], true) ? 50.0 : null,
                'weighment_qty_mt' => in_array($rake->state, ['loaded', 'dispatched'], true) ? 50.2 : null,
                'is_unfit' => $seq === 10 && $rake->state !== 'pending',
                'state' => $rake->state === 'pending' ? 'pending' : ($seq === 10 ? 'unfit' : 'loaded'),
            ]);
        }
    }

    private function seedRakeLoadForRake(Rake $rake): void
    {
        // Only create rake loads for rakes that are in loading/loaded/dispatched states
        if (! in_array($rake->state, ['loading', 'loaded', 'dispatched'], true)) {
            return;
        }

        RakeLoad::query()->firstOrCreate(['rake_id' => $rake->id], [
            'placement_time' => $rake->placement_time,
            'free_time_minutes' => $rake->free_time_minutes ?? 180,
            'status' => $rake->state === 'completed' ? 'completed' : 'in_progress',
        ]);
    }

    private function seedWeighmentForRake(Rake $rake): void
    {
        if (! in_array($rake->state, ['loaded', 'dispatched'], true)) {
            return;
        }

        $rakeLoad = $rake->rakeLoad;
        if (! $rakeLoad) {
            return; // Skip if no rake load exists
        }

        $weighmentTime = $rake->placement_time?->copy()->addMinutes(15) ?? \Illuminate\Support\Facades\Date::now()->subHour();
        Weighment::query()->firstOrCreate([
            'rake_id' => $rake->id,
            'weighment_time' => $weighmentTime,
        ], [
            'weighment_time' => $weighmentTime,
            'rake_load_id' => $rakeLoad->id,
            'total_weight_mt' => (float) ($rake->loaded_weight_mt ?? 2920),
            'status' => 'verified',
            'attempt_no' => 1,
            'train_speed_kmph' => 45.50,
            'remarks' => 'Demo weighment',
            'created_by' => $this->demoUser->id,
        ]);
    }

    private function seedGuardInspectionForRake(Rake $rake): void
    {
        if (! in_array($rake->state, ['loaded', 'dispatched'], true)) {
            return;
        }

        $rakeLoad = $rake->rakeLoad;

        GuardInspection::query()->firstOrCreate(['rake_id' => $rake->id], [
            'rake_load_id' => $rakeLoad?->id,
            'inspection_time' => $rake->placement_time ?? \Illuminate\Support\Facades\Date::now(),
            'is_approved' => true,
            'remarks' => 'Demo guard inspection',
            'created_by' => $this->demoUser->id,
        ]);
    }

    private function seedTxrForRake(Rake $rake): void
    {
        Txr::query()->firstOrCreate(['rake_id' => $rake->id], [
            'inspection_time' => $rake->placement_time ?? \Illuminate\Support\Facades\Date::now()->subHour(),
            'status' => 'approved',
            'remarks' => 'Demo TXR',
            'created_by' => $this->demoUser->id,
        ]);
    }

    private function seedRrPredictionForRake(Rake $rake): void
    {
        RrPrediction::query()->firstOrCreate(['rake_id' => $rake->id], [
            'predicted_weight_mt' => $rake->predicted_weight_mt ?? 2920,
            'predicted_rr_date' => \Illuminate\Support\Facades\Date::now()->addDays(2),
            'prediction_confidence' => 'high',
            'prediction_status' => 'pending',
            'variance_percent' => null,
        ]);
    }

    private function seedRrDocumentForRake(Rake $rake, int $r): void
    {
        $rrNum = 'DEMO-RR-'.$rake->rake_number;
        RrDocument::query()->firstOrCreate(['rr_number' => $rrNum], [
            'rake_id' => $rake->id,
            'rr_received_date' => \Illuminate\Support\Facades\Date::now()->subDays($r),
            'rr_weight_mt' => 2915,
            'rr_details' => '{"demo": true}',
            'document_status' => 'verified',
            'has_discrepancy' => false,
            'created_by' => $this->demoUser->id,
            'updated_by' => $this->demoUser->id,
        ]);
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

            $responsibleParties = ['siding', 'railway', 'transporter', 'plant'];
            $rootCauses = [
                'Equipment breakdown: payloader hydraulic failure during loading',
                'Communication gap: rake placement notice received late',
                'Operational delay: slow loading rate due to coal quality issues',
                'Documentation delay: RR processing delayed by missing paperwork',
            ];

            Penalty::query()->updateOrCreate([
                'rake_id' => $rake->id,
                'penalty_type' => $types[$idx],
                'penalty_date' => \Illuminate\Support\Facades\Date::now()->subDays($r),
            ], [
                'penalty_amount' => $amount,
                'penalty_status' => $idx === 0 ? 'incurred' : 'pending',
                'responsible_party' => $responsibleParties[$idx + ($r - 3)],
                'root_cause' => $rootCauses[$idx + ($r - 3)],
                'description' => $description,
                'remediation_notes' => null,
                'calculation_breakdown' => $breakdown,
            ]);
        }
    }

    private function seedVehicleArrivalsAndUnloadsForSiding(Siding $siding): void
    {
        $vehicles = Vehicle::query()->limit(4)->get();
        $indents = Indent::query()->where('siding_id', $siding->id)->whereIn('state', ['allocated', 'completed'])->limit(4)->get();
        $now = \Illuminate\Support\Facades\Date::now();

        foreach ($vehicles->take(3) as $idx => $vehicle) {
            $arrivedAt = $now->copy()->subHours(6 - $idx);
            $arrival = VehicleArrival::query()->firstOrCreate([
                'siding_id' => $siding->id,
                'vehicle_id' => $vehicle->id,
                'arrived_at' => $arrivedAt,
            ], [
                'indent_id' => ($idx % 2) < $indents->count() ? $indents->get($idx % 2)?->id : null,
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
            ]);

            if ($idx === 0 || $idx === 1) {
                $unload = VehicleUnload::query()->firstOrCreate([
                    'vehicle_arrival_id' => $arrival->id,
                ], [
                    'siding_id' => $siding->id,
                    'vehicle_id' => $vehicle->id,
                    'arrival_time' => $arrivedAt,
                    'jimms_challan_number' => 'DEMO-CH-'.$siding->code.'-'.$idx,
                    'shift' => ['morning', 'evening', 'night'][$idx % 3],
                    'unload_start_time' => $arrivedAt->copy()->addMinutes(10),
                    'unload_end_time' => $idx === 0 ? $arrivedAt->copy()->addMinutes(45) : null,
                    'mine_weight_mt' => 30.0,
                    'weighment_weight_mt' => 30.2,
                    'variance_mt' => 0.2,
                    'state' => $idx === 0 ? 'completed' : 'unloading',
                    'remarks' => 'Demo unload',
                    'created_by' => $this->demoUser->id,
                    'updated_by' => $this->demoUser->id,
                ]);

                // Create steps for unload (completed or in progress)
                $this->createUnloadSteps($unload, new Carbon($arrivedAt), $idx === 0);

                // Create weighments for unload
                $this->createUnloadWeighments($unload, new Carbon($arrivedAt), $arrival, $idx === 0);
            }
        }
    }

    private function seedStockLedgersForSiding(Siding $siding): void
    {
        $arrivals = VehicleArrival::query()->where('siding_id', $siding->id)->where('status', 'completed')->limit(2)->get();
        $opening = 500.0;

        foreach ($arrivals as $idx => $arrival) {
            $qty = (float) $arrival->unloaded_quantity;
            if ($qty <= 0) {
                continue;
            }
            $closing = $opening + $qty;
            StockLedger::query()->firstOrCreate([
                'siding_id' => $siding->id,
                'vehicle_arrival_id' => $arrival->id,
                'transaction_type' => 'receipt',
            ], [
                'quantity_mt' => $qty,
                'opening_balance_mt' => $opening,
                'closing_balance_mt' => $closing,
                'reference_number' => 'DEMO-RCP-'.$siding->code.'-'.($idx + 1),
                'remarks' => 'Demo receipt from vehicle arrival',
                'created_by' => $this->demoUser->id,
            ]);
            $opening = $closing;
        }

        CoalStock::query()->firstOrCreate([
            'siding_id' => $siding->id,
            'as_of_date' => \Illuminate\Support\Facades\Date::today(),
        ], [
            'opening_balance_mt' => 500,
            'receipt_quantity_mt' => 60.4,
            'dispatch_quantity_mt' => 0,
            'closing_balance_mt' => 560.4,
            'remarks' => 'Demo coal stock snapshot',
        ]);
    }

    private function seedAlertsForRakes(\Illuminate\Support\Collection $sidings): void
    {
        $sidingIds = $sidings->pluck('id')->all();
        $rakes = Rake::query()->whereIn('siding_id', $sidingIds)
            ->whereIn('state', ['loading', 'loaded'])
            ->get();

        foreach ($rakes as $rake) {
            if ($rake->state === 'loading') {
                Alert::query()->firstOrCreate([
                    'rake_id' => $rake->id,
                    'type' => 'demurrage_60',
                    'status' => 'active',
                ], [
                    'siding_id' => $rake->siding_id,
                    'user_id' => $this->demoUser->id,
                    'title' => 'Demurrage warning: '.$rake->rake_number,
                    'body' => 'Free time under 60 minutes. Complete loading soon.',
                    'severity' => 'warning',
                    'created_at' => now(),
                ]);
            }
        }

        $overloadRake = Rake::query()->whereIn('siding_id', $sidingIds)->where('state', 'loaded')->first();
        if ($overloadRake) {
            Alert::query()->firstOrCreate([
                'rake_id' => $overloadRake->id,
                'type' => 'overload',
                'status' => 'active',
            ], [
                'siding_id' => $overloadRake->siding_id,
                'user_id' => $this->demoUser->id,
                'title' => 'Overload flag: '.$overloadRake->rake_number,
                'body' => 'Loader vs weighment variance exceeds threshold.',
                'severity' => 'critical',
                'created_at' => now(),
            ]);
        }

        Alert::query()->firstOrCreate([
            'siding_id' => $sidings->first()->id,
            'type' => 'stock_low',
            'status' => 'active',
        ], [
            'user_id' => $this->demoUser->id,
            'title' => 'Low stock alert',
            'body' => 'Siding stock below recommended level.',
            'severity' => 'info',
            'created_at' => now(),
        ]);
    }

    private function seedLoaderPerformanceForSidings(\Illuminate\Support\Collection $sidings): void
    {
        $loaders = Loader::query()->whereIn('siding_id', $sidings->pluck('id'))->get();
        $today = \Illuminate\Support\Facades\Date::today();
        foreach ($loaders->take(5) as $loader) {
            LoaderPerformance::query()->firstOrCreate([
                'loader_id' => $loader->id,
                'as_of_date' => $today,
            ], [
                'rakes_processed' => 4,
                'average_loading_time_minutes' => 165,
                'consistency_variance_minutes' => 12,
                'overload_incidents' => 0,
                'quality_score' => 92,
            ]);
        }
    }

    private function seedSidingPerformanceForSidings(\Illuminate\Support\Collection $sidings): void
    {
        $today = \Illuminate\Support\Facades\Date::today();
        foreach ($sidings->take(3) as $siding) {
            SidingPerformance::query()->firstOrCreate([
                'siding_id' => $siding->id,
                'as_of_date' => $today,
            ], [
                'rakes_processed' => 8,
                'total_penalty_amount' => 0,
                'penalty_incidents' => 0,
                'average_demurrage_hours' => 0,
                'overload_incidents' => 0,
                'closing_stock_mt' => 560.4,
            ]);
        }
    }

    private function seedPowerPlantReceipts(): void
    {
        $plants = PowerPlant::query()->where('is_active', true)->get();
        if ($plants->isEmpty()) {
            return;
        }

        $rakesWithRr = Rake::query()->whereHas('rrDocuments')->with('rrDocuments')->get();
        foreach ($rakesWithRr->take(3) as $idx => $rake) {
            $rr = $rake->rrDocuments()->latest('rr_received_date')->first();
            $plant = $plants->get($idx % $plants->count());
            if (! $plant) {
                continue;
            }
            if (! $rr) {
                continue;
            }
            PowerPlantReceipt::query()->firstOrCreate([
                'rake_id' => $rake->id,
                'power_plant_id' => $plant->id,
            ], [
                'receipt_date' => $rr->rr_received_date,
                'weight_mt' => (float) ($rr->rr_weight_mt ?? 2915),
                'rr_reference' => $rr->rr_number,
                'variance_mt' => 0,
                'variance_pct' => 0,
                'status' => 'received',
                'created_by' => $this->demoUser->id,
            ]);
        }
    }

    private function createUnloadSteps(VehicleUnload $unload, Carbon $arrivedAt, bool $isCompleted = true): void
    {
        $steps = [
            1 => ['Truck Arrived at Siding', 'completed', $arrivedAt],
            2 => ['Gross Weighment', 'completed', $arrivedAt->copy()->addMinutes(5)],
            3 => ['Unloading Started', 'completed', $arrivedAt->copy()->addMinutes(10)],
            4 => ['Tare Weighment', $isCompleted ? 'completed' : 'in_progress', $isCompleted ? $arrivedAt->copy()->addMinutes(40) : $arrivedAt->copy()->addMinutes(40)],
            5 => ['Unload Completed & Stock Updated', $isCompleted ? 'completed' : 'pending', $isCompleted ? $arrivedAt->copy()->addMinutes(45) : null],
        ];

        foreach ($steps as $stepNumber => [$stepName, $status, $timestamp]) {
            \App\Models\VehicleUnloadStep::query()->firstOrCreate([
                'vehicle_unload_id' => $unload->id,
                'step_number' => $stepNumber,
            ], [
                'status' => $status,
                'started_at' => $timestamp,
                'completed_at' => $status === 'completed' ? $timestamp : null,
                'updated_by' => $this->demoUser->id,
            ]);
        }
    }

    private function createUnloadWeighments(VehicleUnload $unload, Carbon $arrivedAt, VehicleArrival $arrival, bool $isCompleted = true): void
    {
        // Create gross weighment (matches arrival weight)
        \App\Models\VehicleUnloadWeighment::query()->firstOrCreate([
            'vehicle_unload_id' => $unload->id,
            'weighment_type' => 'GROSS',
            'weighment_time' => $arrivedAt->copy()->addMinutes(5),
        ], [
            'gross_weight_mt' => $arrival->gross_weight,
            'tare_weight_mt' => null,
            'net_weight_mt' => $arrival->gross_weight,
            'weighment_status' => 'PASS',
            'data_source' => 'weighbridge',
            'weighment_time' => $arrivedAt->copy()->addMinutes(5),
        ]);

        // Create tare weighment (matches arrival tare weight) - only if completed or in progress
        if ($isCompleted) {
            \App\Models\VehicleUnloadWeighment::query()->firstOrCreate([
                'vehicle_unload_id' => $unload->id,
                'weighment_type' => 'TARE',
                'weighment_time' => $arrivedAt->copy()->addMinutes(40),
            ], [
                'gross_weight_mt' => $arrival->gross_weight,
                'tare_weight_mt' => $arrival->tare_weight,
                'net_weight_mt' => (float) $arrival->gross_weight - (float) $arrival->tare_weight,
                'weighment_status' => 'PASS',
                'data_source' => 'weighbridge',
                'weighment_time' => $arrivedAt->copy()->addMinutes(40),
            ]);
        }
    }
}
