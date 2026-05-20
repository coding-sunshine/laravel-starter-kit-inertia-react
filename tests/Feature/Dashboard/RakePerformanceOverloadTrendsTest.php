<?php

declare(strict_types=1);

use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use App\Models\Wagon;
use App\Models\WagonLoading;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function rakeOverloadTrendsUser(): User
{
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('super-admin');

    return $user;
}

test('overload trends requires authentication', function (): void {
    $this->getJson(route('dashboard.rake-performance.overload-trends', [
        'rp_overload_period' => 'today',
    ]))->assertUnauthorized();
});

test('overload trends rejects invalid period', function (): void {
    $user = rakeOverloadTrendsUser();

    $this->actingAs($user)
        ->getJson(route('dashboard.rake-performance.overload-trends', [
            'rp_overload_period' => 'invalid',
        ]))
        ->assertUnprocessable();
});

test('overload trends returns siding-wise daily percentages for today', function (): void {
    $user = rakeOverloadTrendsUser();
    $siding = Siding::factory()->create(['name' => 'Test Siding']);
    $rake = Rake::factory()->create([
        'siding_id' => $siding->id,
        'loading_date' => now()->toDateString(),
    ]);

    $wagonOverload = Wagon::factory()->forRake($rake)->create([
        'pcc_weight_mt' => 100,
    ]);
    $wagonUnderload = Wagon::factory()->forRake($rake)->create([
        'pcc_weight_mt' => 100,
    ]);
    $wagonOk = Wagon::factory()->forRake($rake)->create([
        'pcc_weight_mt' => 100,
    ]);

    WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagonOverload->id,
        'loaded_quantity_mt' => 110,
        'cc_capacity_mt' => 100,
    ]);
    WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagonUnderload->id,
        'loaded_quantity_mt' => 90,
        'cc_capacity_mt' => 100,
    ]);
    WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagonOk->id,
        'loaded_quantity_mt' => 100,
        'cc_capacity_mt' => 100,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('dashboard.rake-performance.overload-trends', [
            'rp_overload_period' => 'today',
            'siding_id' => $siding->id,
        ]));

    $response->assertOk()
        ->assertJsonPath('period', 'today')
        ->assertJsonMissingPath('data.underload_threshold')
        ->assertJsonCount(1, 'data.by_siding');

    $sidingRow = $response->json('data.by_siding.0');
    expect($sidingRow['siding_id'])->toBe($siding->id)
        ->and($sidingRow['summary']['total_wagons'])->toBe(3)
        ->and($sidingRow['summary']['avg_daily_overload_pct'])->toBe(3.3)
        ->and($sidingRow['summary']['avg_daily_underload_pct'])->toBe(3.3);

    $todayPoint = collect($sidingRow['daily'])->first(
        fn (array $d): bool => $d['total_wagons'] > 0,
    );
    expect($todayPoint)->not->toBeNull()
        ->and($todayPoint['overload_pct'])->toBe(3.3)
        ->and($todayPoint['underload_pct'])->toBe(3.3);
});

test('overload trends averages per-wagon severity percent of CC', function (): void {
    $user = rakeOverloadTrendsUser();
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create([
        'siding_id' => $siding->id,
        'loading_date' => now()->toDateString(),
    ]);
    $wagon = Wagon::factory()->forRake($rake)->create(['pcc_weight_mt' => 100]);
    WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'loaded_quantity_mt' => 99,
        'cc_capacity_mt' => 100,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('dashboard.rake-performance.overload-trends', [
            'rp_overload_period' => 'today',
            'siding_id' => $siding->id,
        ]));

    $response->assertOk();
    $todayPoint = collect($response->json('data.by_siding.0.daily'))->first(
        fn (array $d): bool => $d['total_wagons'] > 0,
    );
    expect($todayPoint['underload_pct'])->toBe(1.0)
        ->and($todayPoint['overload_pct'])->toBe(0.0);
});

test('overload trends only queries the requested period', function (): void {
    $user = rakeOverloadTrendsUser();
    $siding = Siding::factory()->create();
    $rakeToday = Rake::factory()->create([
        'siding_id' => $siding->id,
        'loading_date' => now()->toDateString(),
    ]);
    $rakeYesterday = Rake::factory()->create([
        'siding_id' => $siding->id,
        'loading_date' => now()->subDay()->toDateString(),
    ]);

    foreach ([$rakeToday, $rakeYesterday] as $rake) {
        $wagon = Wagon::factory()->forRake($rake)->create(['pcc_weight_mt' => 100]);
        WagonLoading::factory()->create([
            'rake_id' => $rake->id,
            'wagon_id' => $wagon->id,
            'loaded_quantity_mt' => 110,
            'cc_capacity_mt' => 100,
        ]);
    }

    $todayResponse = $this->actingAs($user)
        ->getJson(route('dashboard.rake-performance.overload-trends', [
            'rp_overload_period' => 'today',
            'siding_id' => $siding->id,
        ]));

    $todayResponse->assertOk();
    expect($todayResponse->json('data.by_siding.0.summary.total_wagons'))->toBe(1);

    $yesterdayResponse = $this->actingAs($user)
        ->getJson(route('dashboard.rake-performance.overload-trends', [
            'rp_overload_period' => 'yesterday',
            'siding_id' => $siding->id,
        ]));

    $yesterdayResponse->assertOk();
    expect($yesterdayResponse->json('data.by_siding.0.summary.total_wagons'))->toBe(1);
});
