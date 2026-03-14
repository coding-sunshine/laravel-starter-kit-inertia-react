<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProvisionWordpressSiteJob;
use App\Models\WordpressWebsite;
use Illuminate\Console\Command;

final class ProvisionPendingWordpressSitesCommand extends Command
{
    protected $signature = 'wp:provision-pending {--limit=10 : Maximum number of sites to process}';

    protected $description = 'Dispatch provisioning jobs for WordPress sites in pending or initializing state.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $sites = WordpressWebsite::query()
            ->whereIn('stage', [1, 2])
            ->limit($limit)
            ->get();

        if ($sites->isEmpty()) {
            $this->info('No pending WordPress sites found.');

            return self::SUCCESS;
        }

        foreach ($sites as $site) {
            dispatch(new ProvisionWordpressSiteJob($site));
            $this->line("Dispatched provisioning job for site #{$site->id} ({$site->title})");
        }

        $this->info("Dispatched {$sites->count()} provisioning job(s).");

        return self::SUCCESS;
    }
}
