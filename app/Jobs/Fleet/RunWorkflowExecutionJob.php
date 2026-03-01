<?php

declare(strict_types=1);

namespace App\Jobs\Fleet;

use App\Models\Fleet\WorkflowExecution;
use App\Services\Fleet\WorkflowExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class RunWorkflowExecutionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $workflowExecutionId,
    ) {}

    public function handle(WorkflowExecutionService $service): void
    {
        $execution = WorkflowExecution::find($this->workflowExecutionId);
        if ($execution === null) {
            Log::warning('RunWorkflowExecutionJob: execution not found', ['id' => $this->workflowExecutionId]);
            return;
        }

        try {
            $service->run($execution);
        } catch (\Throwable $e) {
            Log::error('RunWorkflowExecutionJob: failed', [
                'execution_id' => $this->workflowExecutionId,
                'error' => $e->getMessage(),
            ]);
            $execution->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
