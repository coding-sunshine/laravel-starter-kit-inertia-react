<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;

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
        $superAdmin = \App\Models\User::query()->firstOrCreate(['email' => 'superadmin@rrmcs.local'], [
            'name' => 'Super Administrator',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $superAdmin->assignRole('super_admin');

        // Set default organization owner if org exists (created by SidingSeeder with owner_id null)
        \App\Models\Organization::query()->where('slug', 'default')->whereNull('owner_id')->update(['owner_id' => $superAdmin->id]);

        // Create Management User
        $management = \App\Models\User::query()->firstOrCreate(['email' => 'manager@rrmcs.local'], [
            'name' => 'Management Officer',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $management->assignRole('management');

        // Get the default organization and sidings
        $org = \App\Models\Organization::query()->where('slug', 'default')->first();
        $sidings = \App\Models\Siding::all();

        if ($org && $sidings->count() > 0) {
            // Create In-Charge users for each siding
            foreach ($sidings as $siding) {
                $inCharge = \App\Models\User::query()->firstOrCreate(['email' => "incharge_{$siding->code}@rrmcs.local"], [
                    'name' => "Siding In-Charge ({$siding->code})",
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'onboarding_completed' => true,
                ]);
                $inCharge->assignRole('siding_in_charge');

                // Map user to siding
                $inCharge->sidings()->syncWithoutDetaching([
                    $siding->id => ['is_primary' => true],
                ]);

                // Create Operator users for each siding
                $operator = \App\Models\User::query()->firstOrCreate(['email' => "operator_{$siding->code}@rrmcs.local"], [
                    'name' => "Siding Operator ({$siding->code})",
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'onboarding_completed' => true,
                ]);
                $operator->assignRole('siding_operator');

                // Map user to siding
                $operator->sidings()->syncWithoutDetaching([
                    $siding->id => ['is_primary' => true],
                ]);
            }

            // Create Finance user
            $finance = \App\Models\User::query()->firstOrCreate(['email' => 'finance@rrmcs.local'], [
                'name' => 'Finance Officer',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'onboarding_completed' => true,
            ]);
            $finance->assignRole('finance');

            // Map finance user to all sidings
            $finance->sidings()->syncWithoutDetaching(
                $sidings->pluck('id')->toArray()
            );

            // Create Mine Operator (view-only; may use app to see dispatch status)
            $mineOperator = \App\Models\User::query()->firstOrCreate(['email' => 'mine_operator@rrmcs.local'], [
                'name' => 'Mine Operator',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'onboarding_completed' => true,
            ]);
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
}
