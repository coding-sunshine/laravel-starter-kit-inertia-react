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
