<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

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
        'SidingShiftSeeder',
    ];

    /**
     * Run the database seeds for Railway Rake Management Users.
     * Creates test users with different roles for system testing.
     */
    public function run(): void
    {
        // Global roles use organization_id = 0; ensure role assignments use that team.
        resolve(PermissionRegistrar::class)->setPermissionsTeamId(0);

        // === RRMMS-specific seed data (roles: super-admin, admin, user, viewer) ===
        $this->seedRrmmsUsers();
    }

    private function seedRrmmsUsers(): void
    {
        $pakur = \App\Models\Siding::query()->where('code', 'PKUR')->first();
        $dumka = \App\Models\Siding::query()->where('code', 'DUMK')->first();
        $kurwa = \App\Models\Siding::query()->where('code', 'KURWA')->first();
        $allSidings = \App\Models\Siding::query()->get();

        if ($pakur === null || $dumka === null || $kurwa === null) {
            return;
        }

        // Superadmin (global)
        $superAdmin = \App\Models\User::query()->firstOrCreate(['email' => 'superadmin@rmms.local'], [
            'name' => 'Super Admin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $superAdmin->syncRoles(['super-admin']);

        // Dispatch manage admin (truck admin) – access to all sidings for dispatch-related screens
        $dispatchAdmin = \App\Models\User::query()->firstOrCreate(['email' => 'dispatch.admin@rmms.local'], [
            'name' => 'Dispatch Manage Admin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $dispatchAdmin->syncRoles(['dispatch-manage-admin']);
        if ($allSidings->isNotEmpty()) {
            $dispatchAdmin->sidings()->syncWithoutDetaching(
                $allSidings->pluck('id')->mapWithKeys(
                    fn (int $id): array => [$id => ['is_primary' => false]]
                )->all()
            );
        }

        // Admin siding in-charge users
        $this->createAdminForSiding($pakur, 'pakur.sidingincharge@rmms.local', 'Pakur Siding Incharge');
        $this->createAdminForSiding($kurwa, 'kurwa.sidingincharge@rmms.local', 'Kurwa Siding Incharge');
        $this->createAdminForSiding($dumka, 'dumka.sidingincharge@rmms.local', 'Dumka Siding Incharge');

        // Shift users (3 per siding) – Road Dispatch / Daily Vehicle Entries
        $this->createShiftUsersForSiding($pakur);
        $this->createShiftUsersForSiding($kurwa);
        $this->createShiftUsersForSiding($dumka);

        // Empty weighment shift users (3 per siding) – Railway Siding Empty Weighment only
        $this->createEmptyWeighmentShiftUsersForSiding($pakur);
        $this->createEmptyWeighmentShiftUsersForSiding($kurwa);
        $this->createEmptyWeighmentShiftUsersForSiding($dumka);

        // Viewer
        $viewer = \App\Models\User::query()->firstOrCreate(['email' => 'viewer@rmms.local'], [
            'name' => 'Dashboard Viewer',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $viewer->syncRoles(['viewer']);
    }

    private function createAdminForSiding(\App\Models\Siding $siding, string $email, string $name): void
    {
        $admin = \App\Models\User::query()->firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'onboarding_completed' => true,
            'siding_id' => $siding->id,
        ]);

        if ($admin->siding_id !== $siding->id) {
            $admin->siding_id = $siding->id;
            $admin->save();
        }

        $admin->syncRoles(['admin']);

        $admin->sidings()->syncWithoutDetaching([
            $siding->id => ['is_primary' => true],
        ]);
    }

    private function createShiftUsersForSiding(\App\Models\Siding $siding): void
    {
        $baseSlug = str($siding->name)->before(' ')->lower()->toString();

        $shifts = \App\Models\SidingShift::query()
            ->where('siding_id', $siding->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($shifts as $index => $shift) {
            $number = $index + 1;

            $email = "{$baseSlug}.shift{$number}@rmms.local";
            $name = sprintf('%s Shift %d User', str($baseSlug)->title(), $number);

            $user = \App\Models\User::query()->firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'onboarding_completed' => true,
                'siding_id' => $siding->id,
            ]);

            if ($user->siding_id !== $siding->id) {
                $user->siding_id = $siding->id;
                $user->save();
            }

            $user->syncRoles(['user']);

            $user->sidings()->syncWithoutDetaching([
                $siding->id => ['is_primary' => true],
            ]);

            $user->sidingShifts()->syncWithoutDetaching([
                $shift->id => [
                    'assigned_at' => now(),
                    'is_active' => true,
                ],
            ]);
        }
    }

    /**
     * Create 3 users per siding for Railway Siding Empty Weighment (WB shift users).
     * Emails: {siding_slug}.wbshift1@rmms.local, wbshift2, wbshift3.
     */
    private function createEmptyWeighmentShiftUsersForSiding(\App\Models\Siding $siding): void
    {
        $baseSlug = str($siding->name)->before(' ')->lower()->toString();

        $shifts = \App\Models\SidingShift::query()
            ->where('siding_id', $siding->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($shifts as $index => $shift) {
            $number = $index + 1;
            $email = "{$baseSlug}.wbshift{$number}@rmms.local";
            $name = sprintf('%s WB Shift %d', str($baseSlug)->title(), $number);

            $user = \App\Models\User::query()->firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'onboarding_completed' => true,
                'siding_id' => $siding->id,
            ]);

            if ($user->siding_id !== $siding->id) {
                $user->siding_id = $siding->id;
                $user->save();
            }

            $user->syncRoles(['empty-weighment-shift']);

            $user->sidings()->syncWithoutDetaching([
                $siding->id => ['is_primary' => true],
            ]);

            $user->sidingShifts()->syncWithoutDetaching([
                $shift->id => [
                    'assigned_at' => now(),
                    'is_active' => true,
                ],
            ]);
        }
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

        // Ensure Siding exists for 1 (idempotent)
        if (\App\Models\Siding::query()->count() === 0) {
            \App\Models\Siding::factory()->count(5)->create();
        }

        // Ensure SidingShift exists for 2 (idempotent)
        if (\App\Models\SidingShift::query()->count() === 0) {
            \App\Models\SidingShift::factory()->count(5)->create();
        }

        // Ensure SidingShift exists for 3 (idempotent)
        if (\App\Models\SidingShift::query()->count() === 0) {
            \App\Models\SidingShift::factory()->count(5)->create();
        }

        // Ensure Category exists for 4 (idempotent)
        if (\App\Models\Category::query()->count() === 0) {
            \App\Models\Category::factory()->count(5)->create();
        }

        // Ensure Achievement exists for 5 (idempotent)
        if (\App\Models\Achievement::query()->count() === 0) {
            \App\Models\Achievement::factory()->count(5)->create();
        }

        // Ensure Achievement exists for 6 (idempotent)
        if (\App\Models\Achievement::query()->count() === 0) {
            \App\Models\Achievement::factory()->count(5)->create();
        }

        // Ensure Achievement exists for 7 (idempotent)
        if (\App\Models\Achievement::query()->count() === 0) {
            \App\Models\Achievement::factory()->count(5)->create();
        }

        // Ensure Achievement exists for 8 (idempotent)
        if (\App\Models\Achievement::query()->count() === 0) {
            \App\Models\Achievement::factory()->count(5)->create();
        }

        // Ensure Role exists for 9 (idempotent)
        if (\App\Models\Role::query()->count() === 0) {
            \App\Models\Role::factory()->count(5)->create();
        }

        // Ensure Team exists for 10 (idempotent)
        if (\App\Models\Team::query()->count() === 0) {
            \App\Models\Team::factory()->count(5)->create();
        }

        // Ensure Permission exists for 11 (idempotent)
        if (\App\Models\Permission::query()->count() === 0) {
            \App\Models\Permission::factory()->count(5)->create();
        }

        // Ensure Siding exists for 12 (idempotent)
        if (\App\Models\Siding::query()->count() === 0) {
            \App\Models\Siding::factory()->count(5)->create();
        }

        // Ensure Siding exists for 13 (idempotent)
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
