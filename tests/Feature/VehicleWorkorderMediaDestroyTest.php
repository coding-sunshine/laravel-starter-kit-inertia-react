<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Siding;
use App\Models\User;
use App\Models\VehicleWorkorder;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('authenticated user with siding access may delete vehicle workorder media without transport update permission', function (): void {
    $org = Organization::factory()->create();
    $siding = Siding::query()->create([
        'organization_id' => $org->id,
        'name' => 'Media WO Siding',
        'code' => 'MWO',
        'location' => 'Test',
        'station_code' => 'MW',
        'is_active' => true,
    ]);

    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('admin');
    $user->givePermissionTo('sections.transport.view');
    $user->sidings()->attach($siding->id, ['is_primary' => true]);

    $workorder = VehicleWorkorder::query()->create([
        'siding_id' => $siding->id,
        'vehicle_no' => 'RCM01',
        'rcd_pin_no' => 'P1',
        'transport_name' => 'Trans',
        'wo_no_2' => 'W2',
        'work_order_date' => '2025-06-02',
        'tyres' => 6,
        'tare_weight' => 10000,
    ]);

    $fake = UploadedFile::fake()->create('rc-scan.pdf', 10, 'application/pdf');
    $media = $workorder->addMedia($fake->getRealPath())
        ->usingFileName($fake->hashName())
        ->toMediaCollection('vehicle_rc');

    expect(Media::query()->whereKey($media->getKey())->exists())->toBeTrue();

    $this->actingAs($user)
        ->delete(route('vehicle-workorders.destroy-media', [
            'vehicle_workorder' => $workorder,
            'media' => $media->getKey(),
        ]))
        ->assertRedirect(route('vehicle-workorders.edit', ['vehicle_workorder' => $workorder]));

    expect(Media::query()->whereKey($media->getKey())->exists())->toBeFalse();
})->skip(fn (): bool => ! extension_loaded('pdo_sqlite'), 'phpunit.xml uses sqlite :memory:; pdo_sqlite extension required.');
