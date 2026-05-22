<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// When route-based permissions are enabled, keep permissions in sync with named routes daily.
if (config('permission.route_based_enforcement', false)) {
    Schedule::command('permission:sync-routes', ['--silent' => true])->daily();
}

// Remove expired personal data exports (GDPR).
Schedule::command('personal-data-export:clean')->daily();

// Regenerate sitemap for SEO.
Schedule::command('sitemap:generate')->daily();

// Database and file backups (spatie/laravel-backup). Run first, then clean old ones.
Schedule::command('backup:run')->daily()->at('01:00');
Schedule::command('backup:clean')->daily()->at('01:00');

// Database Mail: prune old mail exceptions (martinpetricko/laravel-database-mail).
Schedule::command('model:prune', [
    '--model' => [MartinPetricko\LaravelDatabaseMail\Models\MailException::class],
])->daily();

// Loadrite: ensure polling jobs are dispatched for all configured sidings.
Schedule::command('loadrite:start-polling')->everyFiveMinutes();

// Loadrite: stamp loading_end_time on rakes that have been silent for 6+ hours so
// incoming events don't keep gluing to a stale "open" rake.
Schedule::command('loadrite:close-stale-rakes')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('loadrite-close-stale-rakes');

// Loadrite: deep catch-up sweep. The 5-minute poller only looks back a few
// hours; Loadrite's cloud API can publish an event days late, and an outage
// (e.g. disk-full) can leave a multi-day hole. This re-sweeps the last 3 days
// for every configured siding so no late/lost event is missed permanently.
// Guard the DB read: routes/console.php is evaluated on every artisan call,
// including `migrate` on a fresh database and the test suite's in-memory
// SQLite before migrations run. Querying loadrite_settings unconditionally
// throws "no such table" in those cases.
if (Illuminate\Support\Facades\Schema::hasTable('loadrite_settings')) {
    foreach (App\Models\LoadriteSetting::query()->whereNotNull('siding_id')->pluck('siding_id') as $loadriteSidingId) {
        Schedule::command('loadrite:catchup', [
            '--siding' => $loadriteSidingId,
            '--from' => now()->subDays(3)->format('Y-m-d H:i:s'),
        ])
            ->everySixHours()
            ->withoutOverlapping()
            ->onOneServer()
            ->name("loadrite-catchup-siding-{$loadriteSidingId}");
    }
}

// Manager Brief: regenerate AI-powered manager briefings for all active sidings.
// Guard the DB read: routes/console.php is evaluated on every artisan call,
// including `migrate` on a fresh database and in-memory SQLite before migrations
// run; without the guard, querying the sidings table throws "no such table".
if (Illuminate\Support\Facades\Schema::hasTable('sidings')) {
    Schedule::command('manager-brief:refresh')
        ->hourly()
        ->withoutOverlapping()
        ->onOneServer()
        ->name('manager-brief-refresh');
}

// Backfill pcc_weight_mt on any wagons added since the last run so newly-placed
// rakes get their carrying-capacity from existing fleet data, which the wagon
// status resolver needs to compute Loaded / Overload / Underload.
Schedule::command('wagons:backfill-pcc')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('wagons-backfill-pcc');

// Loadrite: nightly reconciliation — sum of wagon_loading.loaded_quantity_mt
// vs sum of loadrite_events Short Total weights per rake. Logs mismatches.
Schedule::command('loadrite:verify-totals --log')
    ->dailyAt('02:50')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('loadrite-verify-totals');

// RRMCS: check loading rakes for demurrage threshold crossings.
Schedule::command('rrmcs:check-demurrage')->everyFiveMinutes();

// RRMCS: aggregate daily siding performance metrics (penalties, demurrage, stock).
Schedule::command('rrmcs:aggregate-performance')->daily()->at('00:30');

// RRMCS: generate AI-powered penalty insights weekly.
Schedule::command('rrmcs:generate-penalty-insights')->weekly()->mondays()->at('06:00');

// RRMCS: send weekly penalty report email to super-admin and admin users.
Schedule::command('rrmcs:send-weekly-penalty-report')->weekly()->mondays()->at('08:00');

// Billing jobs: metrics, credit expiration, trial reminders.
Schedule::job(new App\Jobs\Billing\GenerateBillingMetrics)->daily()->at('02:00');
Schedule::job(new App\Jobs\Billing\ExpireCredits)->daily()->at('03:00');
Schedule::job(new App\Jobs\Billing\ProcessTrialEndingReminders)->daily()->at('04:00');
Schedule::job(new App\Jobs\Billing\ProcessDunningReminders)->daily()->at('05:00');

// Loadrite: fetch downtime events for all configured sidings into cache table.
Schedule::command('loadrite:fetch-downtime')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('loadrite-fetch-downtime');

// RRMCS: cross-reference Loadrite downtime with DEM reconciliations and flag force-majeure dispute candidates.
Schedule::command('disputes:stitch-force-majeure --lookback=30')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('disputes-stitch-force-majeure');
