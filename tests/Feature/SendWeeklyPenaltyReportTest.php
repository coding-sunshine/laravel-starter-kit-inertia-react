<?php

declare(strict_types=1);

use App\Events\WeeklyPenaltyReportReady;
use App\Models\User;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('dispatches WeeklyPenaltyReportReady event when admin users exist', function (): void {
    Event::fake([WeeklyPenaltyReportReady::class]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->artisan('rrmcs:send-weekly-penalty-report')
        ->assertExitCode(0);

    Event::assertDispatched(WeeklyPenaltyReportReady::class);
});

it('skips dispatch when no eligible recipients', function (): void {
    Event::fake([WeeklyPenaltyReportReady::class]);

    $this->artisan('rrmcs:send-weekly-penalty-report')
        ->assertExitCode(0);

    Event::assertNotDispatched(WeeklyPenaltyReportReady::class);
});
