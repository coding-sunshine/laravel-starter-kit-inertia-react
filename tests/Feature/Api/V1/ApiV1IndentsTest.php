<?php

declare(strict_types=1);

use App\Models\Indent;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('api v1 indents index succeeds when linked rake has loading date', function (): void {
    $siding = Siding::factory()->create();
    $user = User::factory()->withoutTwoFactor()->create(['siding_id' => $siding->id]);
    $user->sidings()->attach($siding->id, ['is_primary' => true]);

    $loadingDate = now()->toDateString();

    $indent = Indent::factory()->create([
        'siding_id' => $siding->id,
        'indent_date' => $loadingDate,
    ]);

    Rake::factory()->for($siding)->create([
        'indent_id' => $indent->id,
        'loading_date' => $loadingDate,
        'rake_serial_number' => 'RAKE-001',
    ]);

    $response = actingAs($user, 'sanctum')->getJson('/api/v1/indents');

    $response->assertOk();
    $response->assertJsonPath('data.0.id', $indent->id);
    $response->assertJsonPath('data.0.rake_serial_number', 'RAKE-001');
    $response->assertJsonPath('data.0.loading_date', $loadingDate);
});

test('unauthenticated request to api v1 indents returns unauthorized', function (): void {
    getJson('/api/v1/indents')->assertUnauthorized();
});
