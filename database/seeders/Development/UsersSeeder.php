<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\User;
use Database\Seeders\Concerns\LoadsJsonData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class UsersSeeder extends Seeder
{
    use LoadsJsonData;

    /**
     * Run the database seeds (idempotent).
     */
    public function run(): void
    {
        $this->seedRelationships();
        $this->seedFromJson();
        $this->seedFromFactory();
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // User model has no belongsTo relationships that need seeding
        // If relationships are added in the future, they will be auto-detected
    }

    /**
     * Seed users from JSON data file (idempotent).
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

                // Use idempotent updateOrCreate for users (email is unique)
                if (isset($userData['email']) && ! empty($userData['email'])) {
                    // Ensure password is set (required field)
                    if (! isset($userData['password'])) {
                        $userData['password'] = Hash::make('password');
                    }

                    // Mark email as verified so seeded users can log in immediately
                    if (! isset($userData['email_verified_at'])) {
                        $userData['email_verified_at'] = now();
                    }

                    User::query()->updateOrCreate(
                        ['email' => $userData['email']],
                        $userData
                    );
                } else {
                    // Fallback to factory if no email
                    $factory = User::factory();
                    if ($factoryState !== null && method_exists($factory, $factoryState)) {
                        $factory = $factory->{$factoryState}();
                    }
                    $factory->create($userData);
                }
            }
        } catch (RuntimeException $e) {
            // JSON file doesn't exist or is invalid - skip silently
            // This allows seeders to work with or without JSON files
        }
    }

    /**
     * Seed users using factory states (idempotent - safe to run multiple times).
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
