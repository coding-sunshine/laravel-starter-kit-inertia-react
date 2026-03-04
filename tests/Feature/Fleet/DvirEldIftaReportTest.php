<?php

declare(strict_types=1);

use App\Actions\CreateOrganizationAction;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\VehicleCheckTemplate;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $this->org = $this->user->defaultOrganization() ?? resolve(CreateOrganizationAction::class)->handle($this->user, 'Test Org');
    $this->session = ['current_organization_id' => $this->org->id];
});

it('renders DVIR wizard index for authenticated user with tenant', function (): void {
    $response = $this->actingAs($this->user)
        ->withSession($this->session)
        ->get(route('fleet.dvir.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Fleet/DvirWizard/Index')
            ->has('vehicles')
            ->has('vehicleCheckTemplates')
            ->has('drivers')
        );
});

it('submits DVIR check and redirects to vehicle check show', function (): void {
    $vehicle = new Vehicle([
        'registration' => 'TEST-DVIR-1',
        'make' => 'Test',
        'model' => 'Vehicle',
        'fuel_type' => 'petrol',
        'vehicle_type' => 'car',
    ]);
    $vehicle->organization_id = $this->org->id;
    $vehicle->save();

    $template = new VehicleCheckTemplate([
        'name' => 'Post-trip DVIR',
        'check_type' => 'post_trip',
        'is_active' => true,
        'checklist' => [
            ['id' => Str::uuid()->toString(), 'label' => 'Lights', 'result_type' => 'pass_fail'],
        ],
    ]);
    $template->organization_id = $this->org->id;
    $template->save();

    $items = [
        [
            'item_index' => 0,
            'label' => 'Lights',
            'result_type' => 'pass_fail',
            'result' => 'pass',
            'value_text' => null,
            'notes' => null,
        ],
    ];

    $response = $this->actingAs($this->user)
        ->withSession($this->session)
        ->post(route('fleet.dvir.store'), [
            'vehicle_id' => $vehicle->id,
            'vehicle_check_template_id' => $template->id,
            'check_date' => now()->format('Y-m-d'),
            'items' => $items,
        ]);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('vehicle-checks');
});

it('renders ELD report index for authenticated user with tenant', function (): void {
    $response = $this->actingAs($this->user)
        ->withSession($this->session)
        ->get(route('fleet.reports.eld'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Fleet/Reports/EldReport')
            ->has('rows')
        );
});

it('renders IFTA report index for authenticated user with tenant', function (): void {
    $response = $this->actingAs($this->user)
        ->withSession($this->session)
        ->get(route('fleet.reports.ifta'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Fleet/Reports/IftaReport')
            ->has('rows')
            ->has('filters')
        );
});
