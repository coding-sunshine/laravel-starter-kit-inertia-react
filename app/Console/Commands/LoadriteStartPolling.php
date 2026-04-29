<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\PollLoadriteJob;
use App\Models\LoadriteSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class LoadriteStartPolling extends Command
{
    protected $signature = 'loadrite:start-polling';

    protected $description = 'Ensure PollLoadriteJob is running for each configured siding. No-op if already polling.';

    public function handle(): int
    {
        $settings = LoadriteSetting::query()->get();

        if ($settings->isEmpty()) {
            $this->warn('No Loadrite settings found. Run loadrite:store-token first.');

            return Command::SUCCESS;
        }

        foreach ($settings as $setting) {
            $lockKey = 'loadrite:polling:'.($setting->siding_id ?? 'global');

            if (Cache::has($lockKey)) {
                $this->info("Siding {$setting->siding_id}: already polling, skipping.");

                continue;
            }

            PollLoadriteJob::dispatch($setting->siding_id ?? 0)->onQueue('loadrite-poll');
            $this->info("Siding {$setting->siding_id}: dispatched PollLoadriteJob.");
        }

        return Command::SUCCESS;
    }
}
