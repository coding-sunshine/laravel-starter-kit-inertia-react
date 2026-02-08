<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;

test('user can attach and detach categories', function (): void {
    $user = User::factory()->create();
    $cat = Category::create(['name' => 'Test Category', 'type' => 'default']);

    $user->attachCategory($cat);
    expect($user->fresh()->categoriesIds())->toContain($cat->id);
    expect($user->hasCategory($cat))->toBeTrue();

    $user->detachCategory($cat);
    expect($user->fresh()->categoriesIds())->toBeEmpty();
    expect($user->hasCategory($cat))->toBeFalse();
});

test('user can sync categories', function (): void {
    $user = User::factory()->create();
    $a = Category::create(['name' => 'A', 'type' => 'default']);
    $b = Category::create(['name' => 'B', 'type' => 'default']);

    $user->syncCategories([$a, $b]);
    expect($user->fresh()->categoriesIds())->toEqual([$a->id, $b->id]);

    $user->syncCategories([$b]);
    expect($user->fresh()->categoriesIds())->toEqual([$b->id]);

    $user->syncCategories([]);
    expect($user->fresh()->categoriesIds())->toBeEmpty();
});
