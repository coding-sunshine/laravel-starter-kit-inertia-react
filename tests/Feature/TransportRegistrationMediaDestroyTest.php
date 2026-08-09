<?php

declare(strict_types=1);

use App\Models\TransportWorkOrderRegistration;
use App\Models\User;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('authenticated user may delete registration media without transport update permission when registration has no siding', function (): void {
    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('admin');
    $user->givePermissionTo('sections.transport.view');

    /** @var TransportWorkOrderRegistration $registration */
    $registration = TransportWorkOrderRegistration::factory()
        ->withWorkOrderNo2('99/'.Str::uuid()->toString())
        ->create();

    $fake = UploadedFile::fake()->createWithContent('pan-scan.pdf', "%PDF-1.4\n%\xC2\xA4\xC2\xA4\xC2\xA4\xC2\xA4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");
    $media = $registration->addMedia($fake->getRealPath())
        ->usingFileName($fake->hashName())
        ->toMediaCollection('pan_documents');

    expect(Media::query()->whereKey($media->getKey())->exists())->toBeTrue();

    $this->actingAs($user)
        ->delete(route('vehicle-workorders.transport-registrations.destroy-media', [
            'tw_registration' => $registration,
            'media' => $media->getKey(),
        ]))
        ->assertRedirect(route('vehicle-workorders.transport-registrations.edit', ['tw_registration' => $registration]));

    expect(Media::query()->whereKey($media->getKey())->exists())->toBeFalse();
})->skip(fn (): bool => ! extension_loaded('pdo_sqlite'), 'phpunit.xml uses sqlite :memory:; pdo_sqlite extension required.');
