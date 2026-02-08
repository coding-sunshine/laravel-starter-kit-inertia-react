<?php

declare(strict_types=1);

use App\Models\User;

test('users without completed onboarding are redirected to onboarding page', function (): void {
    $user = User::factory()->needsOnboarding()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding'));
});

test('users with completed onboarding can access dashboard', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('onboarding page is accessible for incomplete users', function (): void {
    $user = User::factory()->needsOnboarding()->create();

    $this->actingAs($user)
        ->get(route('onboarding'))
        ->assertOk();
});

test('completed users are redirected away from onboarding', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);

    $this->actingAs($user)
        ->get(route('onboarding'))
        ->assertRedirect(route('dashboard'));
});

test('can complete onboarding', function (): void {
    $user = User::factory()->needsOnboarding()->create();

    $this->actingAs($user)
        ->post(route('onboarding.store'))
        ->assertRedirect(route('dashboard'));

    $user->refresh();
    expect($user->onboarding_completed)->toBeTrue();
});

test('logout is accessible without completing onboarding', function (): void {
    $user = User::factory()->needsOnboarding()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect();
});
