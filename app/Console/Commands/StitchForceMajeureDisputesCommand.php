<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\StitchForceMajeureDisputesAction;
use Illuminate\Console\Command;

final class StitchForceMajeureDisputesCommand extends Command
{
    protected $signature = 'disputes:stitch-force-majeure
                            {--lookback=30 : Days of reconciliations to scan}';

    protected $description = 'Cross-reference Loadrite downtime events with DEM reconciliations and flag force-majeure dispute candidates.';

    public function handle(StitchForceMajeureDisputesAction $action): int
    {
        $lookback = (int) $this->option('lookback');
        $outcome = $action->handle($lookback);

        $count = count($outcome->candidates);
        $this->line("Scanned {$outcome->rakesScanned} reconciliations, considered {$outcome->downtimeEventsConsidered} downtime events.");
        $this->info("Flagged {$count} dispute candidate(s).");

        return self::SUCCESS;
    }
}
