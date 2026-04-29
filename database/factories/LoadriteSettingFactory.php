<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

final class LoadriteSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'siding_id' => null,
            'access_token' => 'test-access-token-'.fake()->uuid(),
            'refresh_token' => 'test-refresh-token-'.fake()->uuid(),
            'expires_at' => now()->addHour(),
        ];
    }
}
