<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GeneratePenaltyInsightsAction;
use App\Models\Siding;
use Illuminate\Console\Command;

final class GeneratePenaltyInsightsCommand extends Command
{
    protected $signature = 'rrmcs:generate-penalty-insights';

    protected $description = 'Generate AI-powered penalty insights and recommendations for all sidings';

    public function handle(GeneratePenaltyInsightsAction $action): int
    {
        $sidingIds = Siding::query()->pluck('id')->all();

        if ($sidingIds === []) {
            $this->info('No sidings found.');

            return self::SUCCESS;
        }

        $this->info('Generating penalty insights for '.count($sidingIds).' sidings...');

        $insights = $action->handle($sidingIds);

        if ($insights === null) {
            $this->warn('AI provider not available or no data to analyze.');

            return self::SUCCESS;
        }

        $this->info('Generated '.count($insights).' insights.');

        foreach ($insights as $i => $insight) {
            $this->line(($i + 1).". [{$insight['severity']}] {$insight['title']}");
            $this->line("   {$insight['description']}");
        }

        return self::SUCCESS;
    }
}
