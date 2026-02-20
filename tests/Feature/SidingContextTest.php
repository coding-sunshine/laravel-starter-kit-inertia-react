<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Siding;
use App\Models\User;
use App\Services\SidingContext;
use Database\Seeders\Essential\RakeManagementRolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RakeManagementRolePermissionSeeder::class);
    SidingContext::flush();
});

afterEach(function (): void {
    SidingContext::flush();
});

test('set and get returns the siding', function (): void {
    $org = Organization::factory()->create();
    $siding = Siding::create([
        'organization_id' => $org->id,
        'name' => 'Test Siding',
        'code' => 'TST',
        'location' => 'Test',
        'station_code' => 'TST',
        'is_active' => true,
    ]);

    SidingContext::set($siding);

    expect(SidingContext::check())->toBeTrue();
    expect(SidingContext::id())->toBe($siding->id);
    expect(SidingContext::get()->id)->toBe($siding->id);
});

test('set null clears context', function (): void {
    $org = Organization::factory()->create();
    $siding = Siding::create([
        'organization_id' => $org->id,
        'name' => 'Test Siding',
        'code' => 'TST',
        'location' => 'Test',
        'station_code' => 'TST',
        'is_active' => true,
    ]);

    SidingContext::set($siding);
    expect(SidingContext::check())->toBeTrue();

    SidingContext::set(null);
    expect(SidingContext::check())->toBeFalse();
    expect(SidingContext::id())->toBeNull();
});

test('forget clears context', function (): void {
    $org = Organization::factory()->create();
    $siding = Siding::create([
        'organization_id' => $org->id,
        'name' => 'Test Siding',
        'code' => 'TST',
        'location' => 'Test',
        'station_code' => 'TST',
        'is_active' => true,
    ]);

    SidingContext::set($siding);
    SidingContext::forget();

    expect(SidingContext::check())->toBeFalse();
});

test('activeSidingIds returns current siding when set', function (): void {
    $org = Organization::factory()->create();
    $siding = Siding::create([
        'organization_id' => $org->id,
        'name' => 'Siding A',
        'code' => 'SA',
        'location' => 'Test',
        'station_code' => 'SA',
        'is_active' => true,
    ]);
    $siding2 = Siding::create([
        'organization_id' => $org->id,
        'name' => 'Siding B',
        'code' => 'SB',
        'location' => 'Test',
        'station_code' => 'SB',
        'is_active' => true,
    ]);

    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('siding_operator');
    $user->sidings()->attach([$siding->id, $siding2->id]);

    SidingContext::set($siding);

    $ids = SidingContext::activeSidingIds($user);
    expect($ids)->toBe([$siding->id]);
});

test('activeSidingIds returns all user sidings when no context set', function (): void {
    $org = Organization::factory()->create();
    $siding = Siding::create([
        'organization_id' => $org->id,
        'name' => 'Siding A',
        'code' => 'SA',
        'location' => 'Test',
        'station_code' => 'SA',
        'is_active' => true,
    ]);
    $siding2 = Siding::create([
        'organization_id' => $org->id,
        'name' => 'Siding B',
        'code' => 'SB',
        'location' => 'Test',
        'station_code' => 'SB',
        'is_active' => true,
    ]);

    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('siding_operator');
    $user->sidings()->attach([$siding->id, $siding2->id]);

    SidingContext::flush();

    $ids = SidingContext::activeSidingIds($user);
    expect($ids)->toContain($siding->id);
    expect($ids)->toContain($siding2->id);
});

test('siding switch endpoint changes context', function (): void {
    $org = Organization::factory()->create();
    $siding = Siding::create([
        'organization_id' => $org->id,
        'name' => 'Target Siding',
        'code' => 'TGT',
        'location' => 'Test',
        'station_code' => 'TGT',
        'is_active' => true,
    ]);

    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('siding_operator');
    $user->sidings()->attach($siding->id, ['is_primary' => true]);

    $this->actingAs($user)
        ->post(route('siding.switch'), ['siding_id' => $siding->id])
        ->assertRedirect()
        ->assertSessionHas('status');
});

test('siding switch rejects inaccessible siding', function (): void {
    $org = Organization::factory()->create();
    $siding = Siding::create([
        'organization_id' => $org->id,
        'name' => 'Restricted Siding',
        'code' => 'RST',
        'location' => 'Test',
        'station_code' => 'RST',
        'is_active' => true,
    ]);

    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('siding_operator');
    // Not attached to the siding

    $this->actingAs($user)
        ->post(route('siding.switch'), ['siding_id' => $siding->id])
        ->assertRedirect()
        ->assertSessionHasErrors('siding_id');
});

test('switching to all sidings persists and restores correctly', function (): void {
    $org = Organization::factory()->create();
    $siding = Siding::create([
        'organization_id' => $org->id,
        'name' => 'Siding A',
        'code' => 'SA',
        'location' => 'Test',
        'station_code' => 'SA',
        'is_active' => true,
    ]);
    $siding2 = Siding::create([
        'organization_id' => $org->id,
        'name' => 'Siding B',
        'code' => 'SB',
        'location' => 'Test',
        'station_code' => 'SB',
        'is_active' => true,
    ]);

    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('super_admin');
    $user->sidings()->attach([$siding->id, $siding2->id]);

    // Switch to "All sidings" via endpoint (sends null siding_id)
    $this->actingAs($user)
        ->post(route('siding.switch'), ['siding_id' => null])
        ->assertRedirect()
        ->assertSessionHas('status');

    // Verify session stores the sentinel (0) for "All sidings"
    expect(session('current_siding_id'))->toBe(0);

    // On next request, initForUser should restore "All sidings" — not fall back to primary
    SidingContext::flush();
    SidingContext::initForUser($user);
    expect(SidingContext::check())->toBeFalse();
    expect(SidingContext::isInitialized())->toBeTrue();
});

test('initForUser auto-locks single siding user', function (): void {
    $org = Organization::factory()->create();
    $siding = Siding::create([
        'organization_id' => $org->id,
        'name' => 'Only Siding',
        'code' => 'ONL',
        'location' => 'Test',
        'station_code' => 'ONL',
        'is_active' => true,
    ]);

    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('siding_operator');
    $user->sidings()->attach($siding->id, ['is_primary' => true]);

    SidingContext::flush();
    SidingContext::initForUser($user);

    expect(SidingContext::check())->toBeTrue();
    expect(SidingContext::id())->toBe($siding->id);
});
