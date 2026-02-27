<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Indent;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use App\Models\Wagon;
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
        'UsersSeeder',
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
                'placement_time' => $loadingStart,
                'dispatch_time' => $rakeState === 'completed' ? $loadingEnd : null,
                'rr_expected_date' => $now->copy()->addDays(2),
                'rr_actual_date' => null,
                'loading_free_minutes' => 180,
                'created_by' => $this->demoUser->id,
                'updated_by' => $this->demoUser->id,
            ]);

            $this->seedWagonsForRake($rake);
        }
    }

    private function seedWagonsForRake(Rake $rake): void
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
                'pcc_weight_mt' => 58.0,
                'is_unfit' => $seq === 10 && $rake->state !== 'pending',
                'state' => $rake->state === 'pending' ? 'pending' : ($seq === 10 ? 'unfit' : 'loaded'),
            ]);
        }
    }
}
