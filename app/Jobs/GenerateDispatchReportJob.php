<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateDispatchReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Log;

final class GenerateDispatchReportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    /**
     * @param  list<int>  $sidingIds
     */
    public function __construct(
        private readonly array $sidingIds,
        private readonly array $filters = [],
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GenerateDispatchReport $generateDispatchReport): void
    {
        $count = $generateDispatchReport->handle($this->sidingIds, $this->filters);

        Log::info('DPR queue generation completed.', [
            'siding_ids' => $this->sidingIds,
            'filters' => $this->filters,
            'records_processed' => $count,
            'scoped_date_window' => GenerateDispatchReport::filtersDefineDateWindow($this->filters),
        ]);
    }
}
