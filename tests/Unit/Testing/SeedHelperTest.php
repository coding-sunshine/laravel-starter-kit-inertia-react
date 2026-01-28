<?php

declare(strict_types=1);

use App\Models\User;
use App\Testing\SeedHelper;

it('can seed a model', function (): void {
    $users = SeedHelper::seedFor(User::class, 3);

    expect($users)->toHaveCount(3)
        ->and($users->first())->toBeInstanceOf(User::class);
});

it('throws exception for non-existent model', function (): void {
    expect(fn () => SeedHelper::seedFor('NonExistentModel'))
        ->toThrow(InvalidArgumentException::class);
});

it('can seed multiple models', function (): void {
    $results = SeedHelper::seedMany([
        'users' => ['class' => User::class, 'count' => 2],
    ]);

    expect($results)->toHaveKey('users')
        ->and($results['users'])->toHaveCount(2);
});
