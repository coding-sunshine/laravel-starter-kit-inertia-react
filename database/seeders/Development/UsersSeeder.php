<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\User;
use Database\Seeders\Concerns\LoadsJsonData;
use Illuminate\Database\Seeder;
use RuntimeException;

final class UsersSeeder extends Seeder
{
    use LoadsJsonData;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFromJson();
        $this->seedFromFactory();
    }

    /**
     * Seed users from JSON data file.
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('users.json');

            if (! isset($data['users']) || ! is_array($data['users'])) {
                return;
            }

            foreach ($data['users'] as $userData) {
                $factoryState = $userData['_factory_state'] ?? null;
                unset($userData['_factory_state']);

                $factory = User::factory();

                if ($factoryState !== null && method_exists($factory, $factoryState)) {
                    $factory = $factory->{$factoryState}();
                }

                $factory->create($userData);
            }
        } catch (RuntimeException $e) {
            // JSON file doesn't exist or is invalid - skip silently
            // This allows seeders to work with or without JSON files
        }
    }

    /**
     * Seed users using factory states.
     */
    private function seedFromFactory(): void
    {
        // Create admin users
        User::factory()
            ->admin()
            ->count(2)
            ->create();

        // Create regular users
        User::factory()
            ->count(5)
            ->create();

        // Create unverified users
        User::factory()
            ->unverified()
            ->count(2)
            ->create();
    }
}
