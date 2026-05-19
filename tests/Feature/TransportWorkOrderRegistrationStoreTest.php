<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Siding;
use App\Models\TransportWorkOrderRegistration;
use App\Models\User;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Build sidings expected by TransportWorkOrderRegistrationSidingResolver (codes DUMK, PKUR, KURWA).
 *
 * @return array{dumk: Siding, pkur: Siding, kurwa: Siding}
 */
function transportRegistrationTestSidings(Organization $org): array
{
    return [
        'dumk' => Siding::query()->create([
            'organization_id' => $org->id,
            'name' => 'Test Dumka',
            'code' => 'DUMK',
            'location' => 'Test',
            'station_code' => 'DMK',
            'is_active' => true,
        ]),
        'pkur' => Siding::query()->create([
            'organization_id' => $org->id,
            'name' => 'Test Pakur',
            'code' => 'PKUR',
            'location' => 'Test',
            'station_code' => 'PKU',
            'is_active' => true,
        ]),
        'kurwa' => Siding::query()->create([
            'organization_id' => $org->id,
            'name' => 'Test Kurwa',
            'code' => 'KURWA',
            'location' => 'Test',
            'station_code' => 'KUR',
            'is_active' => true,
        ]),
    ];
}

function transportRegistrationStoreUser(): User
{
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('admin');
    $user->givePermissionTo('sections.transport.create');

    return $user;
}

/** @return array<string, mixed> */
function transportRegistrationRequiredPayload(): array
{
    return [
        'transporter_name' => 'Alpha Logistics',
        'email' => 'alpha-logistics@example.test',
        'legal_name_of_business' => 'Alpha Logistics Private Limited',
        'address' => '1 Warehouse Road',
        'gramin_or_non_gramin' => TransportWorkOrderRegistration::GRAMIN_OR_NON_GRAMIN_GRAMIN,
        'is_active' => '1',
    ];
}

test('transport registration store rejects missing siding_id', function (): void {
    $org = Organization::factory()->create();
    $sidings = transportRegistrationTestSidings($org);
    $user = transportRegistrationStoreUser();
    $user->sidings()->attach($sidings['dumk']->id, ['is_primary' => true]);

    $wo2 = '99/'.Str::uuid()->toString();

    $this->actingAs($user)
        ->from(route('vehicle-workorders.transport-registrations.create'))
        ->post(route('vehicle-workorders.transport-registrations.store'), array_merge(transportRegistrationRequiredPayload(), [
            'work_order_no_1' => null,
            'work_order_no_2' => $wo2,
        ]))
        ->assertSessionHasErrors(['siding_id']);

    expect(TransportWorkOrderRegistration::query()->count())->toBe(0);
});

test('transport registration store rejects work order letters other than D P K', function (): void {
    $org = Organization::factory()->create();
    $sidings = transportRegistrationTestSidings($org);
    $user = transportRegistrationStoreUser();
    $user->sidings()->attach($sidings['dumk']->id, ['is_primary' => true]);

    $wo2 = '88/'.Str::uuid()->toString();

    $this->actingAs($user)
        ->post(route('vehicle-workorders.transport-registrations.store'), array_merge(transportRegistrationRequiredPayload(), [
            'siding_id' => $sidings['dumk']->id,
            'work_order_no_1' => 'A1',
            'work_order_no_2' => $wo2,
        ]))
        ->assertSessionHasErrors(['work_order_no_1']);

    expect(TransportWorkOrderRegistration::query()->count())->toBe(0);
});

test('transport registration store rejects siding that does not match resolved D or P or K siding', function (): void {
    $org = Organization::factory()->create();
    $sidings = transportRegistrationTestSidings($org);
    $user = transportRegistrationStoreUser();
    $user->sidings()->attach([
        $sidings['dumk']->id => ['is_primary' => false],
        $sidings['pkur']->id => ['is_primary' => true],
    ]);

    $wo2 = '77/'.Str::uuid()->toString();

    $this->actingAs($user)
        ->post(route('vehicle-workorders.transport-registrations.store'), array_merge(transportRegistrationRequiredPayload(), [
            'siding_id' => $sidings['pkur']->id,
            'work_order_no_1' => 'D100',
            'work_order_no_2' => $wo2,
        ]))
        ->assertSessionHasErrors(['siding_id']);

    expect(TransportWorkOrderRegistration::query()->count())->toBe(0);
});

test('transport registration store creates record when siding matches derived letter', function (): void {
    $org = Organization::factory()->create();
    $sidings = transportRegistrationTestSidings($org);
    $user = transportRegistrationStoreUser();
    $user->sidings()->attach($sidings['dumk']->id, ['is_primary' => true]);

    $wo2 = '66/'.Str::uuid()->toString();

    $this->actingAs($user)
        ->post(route('vehicle-workorders.transport-registrations.store'), array_merge(transportRegistrationRequiredPayload(), [
            'siding_id' => $sidings['dumk']->id,
            'work_order_no_1' => 'D901',
            'work_order_no_2' => $wo2,
            'transporter_name' => 'Matching Co',
            'legal_name_of_business' => 'Matching Co Legal',
            'email' => 'matching@example.test',
        ]))
        ->assertRedirect(route('vehicle-workorders.index', ['view' => 'transporters']))
        ->assertSessionMissing('errors');

    $registration = TransportWorkOrderRegistration::query()->first();
    expect($registration)->not->toBeNull()
        ->and((int) $registration->siding_id)->toBe($sidings['dumk']->id)
        ->and($registration->work_order_no_1)->toBe('D901')
        ->and($registration->work_order_no_2)->toBe($wo2)
        ->and($registration->email)->toBe('matching@example.test')
        ->and($registration->is_active)->toBeTrue();
});

test('transport registration store rejects missing email', function (): void {
    $org = Organization::factory()->create();
    $sidings = transportRegistrationTestSidings($org);
    $user = transportRegistrationStoreUser();
    $user->sidings()->attach($sidings['dumk']->id, ['is_primary' => true]);

    $wo2 = '55/'.Str::uuid()->toString();

    $payload = transportRegistrationRequiredPayload();
    unset($payload['email']);

    $this->actingAs($user)
        ->post(route('vehicle-workorders.transport-registrations.store'), array_merge($payload, [
            'siding_id' => $sidings['dumk']->id,
            'work_order_no_2' => $wo2,
        ]))
        ->assertSessionHasErrors(['email']);

    expect(TransportWorkOrderRegistration::query()->count())->toBe(0);
});
