<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Siding;
use App\Models\TransportWorkOrderRegistration;
use App\Models\User;
use App\Models\VehicleWorkorder;
use Carbon\CarbonImmutable;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/** @return array{sidingA: Siding, sidingB: Siding} */
function vehicleWorkorderSearchSidings(Organization $org): array
{
    return [
        'sidingA' => Siding::query()->create([
            'organization_id' => $org->id,
            'name' => 'Search Test Siding A',
            'code' => 'SRCA',
            'location' => 'Test',
            'station_code' => 'SA',
            'is_active' => true,
        ]),
        'sidingB' => Siding::query()->create([
            'organization_id' => $org->id,
            'name' => 'Search Test Siding B',
            'code' => 'SRCB',
            'location' => 'Test',
            'station_code' => 'SB',
            'is_active' => true,
        ]),
    ];
}

function vehicleWorkorderSearchUser(): User
{
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('admin');
    $user->givePermissionTo(['sections.transport.view', 'sections.transport.update']);

    return $user;
}

/** @return array<string, mixed> */
function minimalVehicleWorkorderUpdatePayload(Siding $siding, array $overrides = []): array
{
    return array_merge([
        'siding_id' => $siding->id,
        'vehicle_no' => 'UPTST2026',
        'rcd_pin_no' => 'RCDX',
        'transport_name' => 'Payload Transport Ltd',
        'wo_no_2' => 'P/WO-PAYLOAD',
        'work_order_date' => '2025-06-10',
        'tyres' => 6,
        'tare_weight' => '15000',
    ], $overrides);
}

test('guest cannot access transport registration search', function (): void {
    $this->get(route('vehicle-workorders.transport-registrations.search'))
        ->assertRedirect();
});

test('search returns active registrations only within user siding visibility with mapped defaults', function (): void {
    $org = Organization::factory()->create();
    $sidings = vehicleWorkorderSearchSidings($org);
    $user = vehicleWorkorderSearchUser();
    $user->sidings()->attach($sidings['sidingA']->id, ['is_primary' => true]);

    $wo1 = 'D/'.Str::uuid()->toString();
    $reference = 'REF-'.Str::uuid()->toString();

    $onAccessibleSiding = TransportWorkOrderRegistration::factory()
        ->forSiding($sidings['sidingA'])
        ->create([
            'transporter_name' => 'Scoped Alpha Transporter',
            'work_order_no_1' => $wo1,
            'work_order_no_2' => 'P/'.Str::uuid()->toString(),
            'work_order_date' => CarbonImmutable::parse('2025-06-10'),
            'legal_name_of_business' => 'Scoped Legal Name Ltd',
            'trade_name' => 'Should Not Win',
            'pan_card' => 'ABCDE1234F',
            'reference_no' => $reference,
            'is_active' => true,
            'email' => 'scoped-alpha-'.Str::uuid()->toString().'@example.test',
        ]);

    $onOtherSiding = TransportWorkOrderRegistration::factory()
        ->forSiding($sidings['sidingB'])
        ->create([
            'transporter_name' => 'Other Siding Only',
            'work_order_no_1' => 'D/'.Str::uuid()->toString(),
            'is_active' => true,
            'email' => 'other-siding-'.Str::uuid()->toString().'@example.test',
        ]);

    TransportWorkOrderRegistration::factory()
        ->forSiding($sidings['sidingA'])
        ->create([
            'transporter_name' => 'Inactive Row',
            'work_order_no_1' => 'D/'.Str::uuid()->toString(),
            'is_active' => false,
            'email' => 'inactive-'.Str::uuid()->toString().'@example.test',
        ]);

    $globalRegistration = TransportWorkOrderRegistration::factory()->create([
        'siding_id' => null,
        'transporter_name' => 'Global Transporter',
        'work_order_no_1' => 'D/'.Str::uuid()->toString(),
        'is_active' => true,
        'email' => 'global-'.Str::uuid()->toString().'@example.test',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('vehicle-workorders.transport-registrations.search'));

    $response->assertOk()->assertJsonStructure(['data']);

    $rows = $response->json('data');
    $ids = collect($rows)->pluck('id')->all();

    expect($ids)->toContain($onAccessibleSiding->id)
        ->toContain($globalRegistration->id)
        ->not->toContain($onOtherSiding->id);

    $scoped = collect($rows)->firstWhere('id', $onAccessibleSiding->id);
    expect($scoped)->toBeArray()
        ->and($scoped['label'])->toContain('Scoped Alpha Transporter')
        ->and($scoped['defaults']['wo_no'])->toBe($wo1)
        ->and($scoped['defaults']['transport_name'])->toBe('Scoped Alpha Transporter')
        ->and($scoped['defaults']['proprietor_name'])->toBe('Scoped Legal Name Ltd')
        ->and($scoped['defaults']['referenced'])->toBe($reference)
        ->and($scoped['defaults']['pan_no'])->toBe('ABCDE1234F')
        ->and($scoped['defaults']['work_order_date'])->toBe('2025-06-10')
        ->and((string) $scoped['defaults']['siding_id'])->toBe((string) $sidings['sidingA']->id);

    $global = collect($rows)->firstWhere('id', $globalRegistration->id);
    expect($global)->toBeArray()
        ->and(array_key_exists('siding_id', $global['defaults']))->toBeFalse();
});

test('search respects q filter on transporter and work order fields', function (): void {
    $org = Organization::factory()->create();
    $sidings = vehicleWorkorderSearchSidings($org);
    $user = vehicleWorkorderSearchUser();
    $user->sidings()->attach($sidings['sidingA']->id, ['is_primary' => true]);

    $needle = 'UniqueNeedle'.Str::uuid()->toString();

    $match = TransportWorkOrderRegistration::factory()
        ->forSiding($sidings['sidingA'])
        ->create([
            'transporter_name' => $needle.' Transporter',
            'work_order_no_1' => 'D/'.Str::uuid()->toString(),
            'is_active' => true,
            'email' => 'needle-match-'.Str::uuid()->toString().'@example.test',
        ]);

    TransportWorkOrderRegistration::factory()
        ->forSiding($sidings['sidingA'])
        ->create([
            'transporter_name' => 'Unrelated Name',
            'work_order_no_1' => 'D/'.Str::uuid()->toString(),
            'is_active' => true,
            'email' => 'needle-miss-'.Str::uuid()->toString().'@example.test',
        ]);

    $response = $this->actingAs($user)
        ->getJson(route('vehicle-workorders.transport-registrations.search', [
            'q' => $needle,
        ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($match->id);
});

test('edit passes matchedTransportRegistration when workorder uniquely matches registration', function (): void {
    $org = Organization::factory()->create();
    $sidings = vehicleWorkorderSearchSidings($org);
    $user = vehicleWorkorderSearchUser();
    $user->sidings()->attach($sidings['sidingA']->id, ['is_primary' => true]);

    $transport = 'MatchCo '.Str::uuid()->toString();
    $wo = 'D/'.Str::uuid()->toString();

    $registration = TransportWorkOrderRegistration::factory()
        ->forSiding($sidings['sidingA'])
        ->create([
            'transporter_name' => $transport,
            'work_order_no_1' => $wo,
            'is_active' => true,
            'email' => 'match-co-'.Str::uuid()->toString().'@example.test',
        ]);

    $workorder = VehicleWorkorder::query()->create([
        'siding_id' => $sidings['sidingA']->id,
        'vehicle_no' => 'MA01TT2026',
        'transport_name' => $transport,
        'wo_no' => $wo,
    ]);

    $this->actingAs($user)
        ->get(route('vehicle-workorders.edit', ['vehicle_workorder' => $workorder]))
        ->assertInertia(fn ($page) => $page
            ->component('VehicleWorkorders/Edit')
            ->has('sidings')
            ->has('matchedTransportRegistration')
            ->where('matchedTransportRegistration.id', $registration->id)
        );
});

test('edit omits matchedTransportRegistration when multiple registrations match', function (): void {
    $org = Organization::factory()->create();
    $sidings = vehicleWorkorderSearchSidings($org);
    $user = vehicleWorkorderSearchUser();
    $user->sidings()->attach($sidings['sidingA']->id, ['is_primary' => true]);

    $transport = 'DupMatch '.Str::uuid()->toString();
    $wo = 'D/'.Str::uuid()->toString();

    TransportWorkOrderRegistration::factory()
        ->forSiding($sidings['sidingA'])
        ->create([
            'transporter_name' => $transport,
            'work_order_no_1' => $wo,
            'is_active' => true,
            'email' => 'dup-a-'.Str::uuid()->toString().'@example.test',
        ]);

    TransportWorkOrderRegistration::factory()
        ->forSiding($sidings['sidingA'])
        ->create([
            'transporter_name' => $transport,
            'work_order_no_1' => $wo,
            'is_active' => true,
            'email' => 'dup-b-'.Str::uuid()->toString().'@example.test',
        ]);

    $workorder = VehicleWorkorder::query()->create([
        'siding_id' => $sidings['sidingA']->id,
        'vehicle_no' => 'DU01PP2026',
        'transport_name' => $transport,
        'wo_no' => $wo,
    ]);

    $this->actingAs($user)
        ->get(route('vehicle-workorders.edit', ['vehicle_workorder' => $workorder]))
        ->assertInertia(fn ($page) => $page
            ->component('VehicleWorkorders/Edit')
            ->has('sidings')
            ->where('matchedTransportRegistration', null)
        );
});

test('vehicle workorder update persists siding_id when user can access both sidings', function (): void {
    $org = Organization::factory()->create();
    $sidings = vehicleWorkorderSearchSidings($org);
    $user = vehicleWorkorderSearchUser();
    $user->sidings()->attach($sidings['sidingA']->id, ['is_primary' => true]);
    $user->sidings()->attach($sidings['sidingB']->id);

    $workorder = VehicleWorkorder::query()->create([
        'siding_id' => $sidings['sidingA']->id,
        'vehicle_no' => 'SIDMV2026',
    ]);

    $this->actingAs($user)
        ->put(route('vehicle-workorders.update', ['vehicle_workorder' => $workorder]), minimalVehicleWorkorderUpdatePayload($sidings['sidingB'], [
            'vehicle_no' => 'SIDMV2026',
        ]))
        ->assertRedirect(route('vehicle-workorders.index'))
        ->assertSessionHasNoErrors();

    expect((int) $workorder->fresh()->siding_id)->toBe((int) $sidings['sidingB']->id);
});
