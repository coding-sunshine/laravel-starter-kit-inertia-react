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
    public function __construct(
        private readonly ?int $sidingId = null,
        private readonly array $filters = [],
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GenerateDispatchReport $generateDispatchReport): void
    {
        $count = $generateDispatchReport->handle($this->sidingId, $this->filters);

        Log::info('DPR queue generation completed.', [
            'siding_id' => $this->sidingId,
            'filters' => $this->filters,
            'records_processed' => $count,
        ]);
    }
}
