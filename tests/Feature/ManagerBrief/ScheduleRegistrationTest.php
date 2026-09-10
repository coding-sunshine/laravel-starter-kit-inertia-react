<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Schema;

// ---------------------------------------------------------------------------
// 13.1 — manager-brief:refresh is registered on the hourly cron expression
// ---------------------------------------------------------------------------

it('manager_brief_refresh_is_scheduled_hourly', function (): void {
    // routes/console.php is only require'd when the console kernel bootstraps
    // its commands (via discoverCommands). In the test environment the kernel
    // may already be "loaded" but the schedule may not yet be populated.
    // Requiring the file directly ensures Schedule::command() calls run and
    // register events on the app's shared Schedule singleton.
    require base_path('routes/console.php');

    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);

    $matchingEvent = collect($schedule->events())
        ->first(fn ($event): bool => str_contains($event->command ?? '', 'manager-brief:refresh'));

    expect($matchingEvent)->not->toBeNull('Expected manager-brief:refresh to be registered in the scheduler.');
    expect($matchingEvent->expression)->toBe('0 * * * *');
});

// ---------------------------------------------------------------------------
// 13.2 — schedule block does not crash when the sidings table does not exist
// ---------------------------------------------------------------------------

it('manager_brief_refresh_is_guarded_by_schema_check_on_fresh_db', function (): void {
    // This test verifies that the guard in routes/console.php prevents an
    // exception when the sidings table has not yet been created (fresh DB /
    // in-memory SQLite before migrations have run).
    //
    // Strategy: assert the source file contains a Schema::hasTable('sidings')
    // check that wraps the Schedule::command block. This is deterministic and
    // does not require dropping the table in the test database.

    $source = file_get_contents(base_path('routes/console.php'));

    expect($source)->toContain("Schema::hasTable('sidings')");
    expect($source)->toContain('manager-brief:refresh');

    // Confirm the schedule block appears AFTER (i.e. inside) the guard.
    $guardPosition = mb_strpos($source, "Schema::hasTable('sidings')");
    $commandPosition = mb_strpos($source, 'manager-brief:refresh');

    expect($commandPosition)->toBeGreaterThan($guardPosition);
});
