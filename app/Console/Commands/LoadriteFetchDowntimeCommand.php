<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\FetchLoadriteDowntimeJob;
use App\Models\LoadriteSetting;
use Illuminate\Console\Command;

final class LoadriteFetchDowntimeCommand extends Command
{
    protected $signature = 'loadrite:fetch-downtime
                            {--lookback=7 : Days of downtime to fetch}';

    protected $description = 'Fetch Loadrite downtime events for every configured siding into the local cache table.';

    public function handle(): int
    {
        $settings = LoadriteSetting::query()->whereNotNull('siding_id')->get();

        if ($settings->isEmpty()) {
            $this->warn('No loadrite_settings rows found. Nothing to fetch.');

            return self::SUCCESS;
        }

        $lookback = (int) $this->option('lookback');

        foreach ($settings as $setting) {
            FetchLoadriteDowntimeJob::dispatch($setting->siding_id, $lookback)
                ->onQueue('loadrite-poll');
            $this->line("Dispatched fetch for siding_id={$setting->siding_id}");
        }

        $this->info("Dispatched {$settings->count()} fetch job(s).");

        return self::SUCCESS;
    }
}
