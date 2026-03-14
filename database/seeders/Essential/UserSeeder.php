<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;
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
}
