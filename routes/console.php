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
