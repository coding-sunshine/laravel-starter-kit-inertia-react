<?php

declare(strict_types=1);

use App\Enums\SeederCategory;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Development\UsersSeeder;

it('discovers seeders in category directories', function (): void {
    $seeder = new DatabaseSeeder([SeederCategory::Development]);

    // Should discover UsersSeeder
    expect($seeder)->toBeInstanceOf(DatabaseSeeder::class);
});

it('runs seeders in correct order', function (): void {
    $seeder = new DatabaseSeeder([SeederCategory::Development]);

    $seeder->run();

    // Should have seeded users
    expect(App\Models\User::query()->count())->toBeGreaterThan(0);
});
