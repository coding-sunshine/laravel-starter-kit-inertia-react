<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GeneratePenaltyPredictionsAction;
use Illuminate\Console\Command;

final class GeneratePenaltyPredictionsCommand extends Command
{
    protected $signature = 'penalties:predict';

    protected $description = 'Generate AI-powered penalty risk predictions for all active sidings';

    public function handle(GeneratePenaltyPredictionsAction $action): int
    {
        $this->info('Generating penalty predictions...');

        $count = $action->handle();

        $this->info("Created {$count} predictions.");

        return self::SUCCESS;
    }
}
