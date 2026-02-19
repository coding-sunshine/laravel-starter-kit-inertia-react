<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;
use RuntimeException;

final class UserSeeder extends Seeder
{
    /**
     * Seeder dependencies (roles and sidings must exist first).
     *
     * @var array<string>
     */
    public array $dependencies = [
        'RolesAndPermissionsSeeder',
        'RakeManagementRolePermissionSeeder',
        'SidingSeeder',
    ];

    /**
     * Run the database seeds for Railway Rake Management Users.
     * Creates test users with different roles for system testing.
     */
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = \App\Models\User::firstOrCreate(
            ['email' => 'superadmin@rrmcs.local'],
            [
                'name' => 'Super Administrator',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'onboarding_completed' => true,
            ]
        );
        $superAdmin->assignRole('super_admin');

        // Set default organization owner if org exists (created by SidingSeeder with owner_id null)
        \App\Models\Organization::where('slug', 'default')->whereNull('owner_id')->update(['owner_id' => $superAdmin->id]);

        // Create Management User
        $management = \App\Models\User::firstOrCreate(
            ['email' => 'manager@rrmcs.local'],
            [
                'name' => 'Management Officer',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'onboarding_completed' => true,
            ]
        );
        $management->assignRole('management');

        // Get the default organization and sidings
        $org = \App\Models\Organization::where('slug', 'default')->first();
        $sidings = \App\Models\Siding::all();

        if ($org && $sidings->count() > 0) {
            // Create In-Charge users for each siding
            foreach ($sidings as $siding) {
                $inCharge = \App\Models\User::firstOrCreate(
                    ['email' => "incharge_{$siding->code}@rrmcs.local"],
                    [
                        'name' => "Siding In-Charge ({$siding->code})",
                        'password' => bcrypt('password'),
                        'email_verified_at' => now(),
                        'onboarding_completed' => true,
                    ]
                );
                $inCharge->assignRole('siding_in_charge');

                // Map user to siding
                $inCharge->sidings()->syncWithoutDetaching([
                    $siding->id => ['is_primary' => true],
                ]);

                // Create Operator users for each siding
                $operator = \App\Models\User::firstOrCreate(
                    ['email' => "operator_{$siding->code}@rrmcs.local"],
                    [
                        'name' => "Siding Operator ({$siding->code})",
                        'password' => bcrypt('password'),
                        'email_verified_at' => now(),
                        'onboarding_completed' => true,
                    ]
                );
                $operator->assignRole('siding_operator');

                // Map user to siding
                $operator->sidings()->syncWithoutDetaching([
                    $siding->id => ['is_primary' => true],
                ]);
            }

            // Create Finance user
            $finance = \App\Models\User::firstOrCreate(
                ['email' => 'finance@rrmcs.local'],
                [
                    'name' => 'Finance Officer',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'onboarding_completed' => true,
                ]
            );
            $finance->assignRole('finance');

            // Map finance user to all sidings
            $finance->sidings()->syncWithoutDetaching(
                $sidings->pluck('id')->toArray()
            );

            // Create Mine Operator (view-only; may use app to see dispatch status)
            $mineOperator = \App\Models\User::firstOrCreate(
                ['email' => 'mine_operator@rrmcs.local'],
                [
                    'name' => 'Mine Operator',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'onboarding_completed' => true,
                ]
            );
            $mineOperator->assignRole('mine_operator');
            $mineOperator->sidings()->syncWithoutDetaching(
                $sidings->pluck('id')->toArray()
            );
        }

        // Assign management and super admin to all sidings
        foreach ($sidings as $siding) {
            $superAdmin->sidings()->syncWithoutDetaching([
                $siding->id => ['is_primary' => false],
            ]);
            $management->sidings()->syncWithoutDetaching([
                $siding->id => ['is_primary' => false],
            ]);
        }

        // Ensure all RRMCS seeded users can access dashboard (skip onboarding)
        $rrmcsEmails = [
            'superadmin@rrmcs.local',
            'manager@rrmcs.local',
            'finance@rrmcs.local',
            'mine_operator@rrmcs.local',
        ];
        foreach ($sidings as $siding) {
            $rrmcsEmails[] = "incharge_{$siding->code}@rrmcs.local";
            $rrmcsEmails[] = "operator_{$siding->code}@rrmcs.local";
        }
        \App\Models\User::query()
            ->whereIn('email', array_unique($rrmcsEmails))
            ->update(['onboarding_completed' => true]);
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Organization exists for 0 (idempotent)
        if (\App\Models\Organization::query()->count() === 0) {
            \App\Models\Organization::factory()->count(5)->create();
        }

        // Ensure Category exists for 1 (idempotent)
        if (\App\Models\Category::query()->count() === 0) {
            \App\Models\Category::factory()->count(5)->create();
        }

        // Ensure Achievement exists for 2 (idempotent)
        if (\App\Models\Achievement::query()->count() === 0) {
            \App\Models\Achievement::factory()->count(5)->create();
        }

        // Ensure Achievement exists for 3 (idempotent)
        if (\App\Models\Achievement::query()->count() === 0) {
            \App\Models\Achievement::factory()->count(5)->create();
        }

        // Ensure Achievement exists for 4 (idempotent)
        if (\App\Models\Achievement::query()->count() === 0) {
            \App\Models\Achievement::factory()->count(5)->create();
        }

        // Ensure Achievement exists for 5 (idempotent)
        if (\App\Models\Achievement::query()->count() === 0) {
            \App\Models\Achievement::factory()->count(5)->create();
        }

        // Ensure Role exists for 6 (idempotent)
        if (\App\Models\Role::query()->count() === 0) {
            \App\Models\Role::factory()->count(5)->create();
        }

        // Ensure Permission exists for 7 (idempotent)
        if (\App\Models\Permission::query()->count() === 0) {
            \App\Models\Permission::factory()->count(5)->create();
        }

        // Ensure Siding exists for 8 (idempotent)
        if (\App\Models\Siding::query()->count() === 0) {
            \App\Models\Siding::factory()->count(5)->create();
        }

        // Ensure Siding exists for 9 (idempotent)
        if (\App\Models\Siding::query()->count() === 0) {
            \App\Models\Siding::factory()->count(5)->create();
        }

        // Note: hasMany relationships are seeded after main model creation
    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('users.json');

            if (! isset($data['users']) || ! is_array($data['users'])) {
                return;
            }

            foreach ($data['users'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (isset($itemData['email']) && ! empty($itemData['email'])) {
                    User::query()->updateOrCreate(
                        ['email' => $itemData['email']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = User::factory();
                    if ($factoryState !== null && method_exists($factory, $factoryState)) {
                        $factory = $factory->{$factoryState}();
                    }
                    $factory->create($itemData);
                }
            }
        } catch (RuntimeException $e) {
            // JSON file doesn't exist or is invalid - skip silently
        }
    }

    /**
     * Seed using factory (idempotent - safe to run multiple times).
     */
    private function seedFromFactory(): void
    {
        // Generate seed data with factory
        // Note: Factory creates are not idempotent by default
        // For true idempotency, use updateOrCreate in seedFromJson or add unique constraints
        User::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(User::factory(), 'admin')) {
            User::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}
