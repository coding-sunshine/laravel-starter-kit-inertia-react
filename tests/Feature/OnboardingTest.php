<?php

declare(strict_types=1);

use App\Features\OnboardingFeature;
use App\Models\User;
use Laravel\Pennant\Feature;

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

test('completed users can view onboarding page again for review', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('onboarding'))
        ->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('onboarding/show')
        ->where('alreadyCompleted', true)
        ->has('initialStep'));
});

test('onboarding show returns initial step for progress persistence', function (): void {
    $user = User::factory()->needsOnboarding()->create([
        'onboarding_steps_completed' => ['current_step' => 1],
    ]);

    $response = $this->actingAs($user)
        ->get(route('onboarding'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/show')
            ->where('initialStep', 1));
});

test('onboarding update persists current step', function (): void {
    $user = User::factory()->needsOnboarding()->create();

    $this->actingAs($user)
        ->put(route('onboarding.update'), ['current_step' => 2])
        ->assertRedirect();

    $user->refresh();
    expect($user->onboarding_steps_completed)->toBeArray();
    expect($user->onboarding_steps_completed['current_step'] ?? null)->toBe(2);
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

test('when onboarding feature is inactive user can access dashboard without completing onboarding', function (): void {
    $user = User::factory()->needsOnboarding()->create();
    Feature::for($user)->deactivate(OnboardingFeature::class);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
