<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('allows admin to access panel', function (): void {
    actsAsFilamentAdmin($this);

    $response = $this->get('/admin');

    $response->assertOk();
});

it('allows super-admin to access panel', function (): void {
    actsAsFilamentAdmin($this, 'super-admin');

    $response = $this->get('/admin');

    $response->assertOk();
});

it('allows admin to open users list', function (): void {
    actsAsFilamentAdmin($this);

    $response = $this->get('/admin/users');

    $response->assertOk();
});

it('denies user without access admin panel permission', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'regular@test.example',
        'password' => Hash::make('password'),
    ]);
    $user->assignRole('user');

    $response = $this->actingAs($user)->get('/admin');

    $response->assertForbidden();
});

it('redirects guest to login when visiting admin', function (): void {
    $response = $this->get('/admin');

    $response->assertRedirect('/admin/login');
});
