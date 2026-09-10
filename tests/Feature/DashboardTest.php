<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('authenticated user with completed onboarding can access dashboard', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('super-admin');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('auth')
            ->where('auth.user.id', $user->id)
            ->has('auth.permissions')
            ->has('auth.roles')
        );
});

test('dashboard requires authentication', function (): void {
    $this->get(route('dashboard'))
        ->assertRedirect();
});
