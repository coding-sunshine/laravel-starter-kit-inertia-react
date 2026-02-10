<?php

declare(strict_types=1);

use App\Features\BlogFeature;
use App\Models\User;
use Laravel\Pennant\Feature;

test('authenticated user with blog feature inactive receives 404 on blog index', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    Feature::for($user)->deactivate(BlogFeature::class);

    $this->actingAs($user)
        ->get(route('blog.index'))
        ->assertNotFound();
});

test('guest can access blog index when default is on', function (): void {
    $this->get(route('blog.index'))
        ->assertOk();
});

test('authenticated user with blog feature active can access blog index', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);

    $this->actingAs($user)
        ->get(route('blog.index'))
        ->assertOk();
});
