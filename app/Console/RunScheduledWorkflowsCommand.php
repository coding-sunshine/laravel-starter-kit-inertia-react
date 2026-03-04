<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\Fleet\RunWorkflowExecutionJob;
use App\Models\Fleet\WorkflowDefinition;
use App\Models\Fleet\WorkflowExecution;
use Illuminate\Console\Command;

final class RunScheduledWorkflowsCommand extends Command
{
    protected $signature = 'workflows:run-scheduled';

    protected $description = 'Start workflow executions for definitions with trigger_type=schedule whose schedule is due.';

    public function handle(): int
    {
        $definitions = WorkflowDefinition::query()
            ->where('is_active', true)
            ->where('trigger_type', 'schedule')
            ->get();

        $started = 0;
        foreach ($definitions as $definition) {
            $config = $definition->trigger_config ?? [];
            $frequency = $config['frequency'] ?? null;

            if ($frequency === 'daily' && ! $this->isDailyDue($definition)) {
                continue;
            }
            if ($frequency !== 'daily' && empty($config)) {
                continue;
            }

            $execution = WorkflowExecution::query()->create([
                'workflow_definition_id' => $definition->id,
                'started_at' => now(),
                'trigger_event' => 'schedule',
                'trigger_entity_type' => null,
                'trigger_entity_id' => null,
                'trigger_data' => ['scheduled_at' => now()->toIso8601String()],
                'status' => 'pending',
            ]);

            dispatch(new RunWorkflowExecutionJob($execution->id));
            $started++;
        }

        if ($started > 0) {
            $this->info("Started {$started} scheduled workflow execution(s).");
        }

        return self::SUCCESS;
    }

    private function isDailyDue(WorkflowDefinition $definition): bool
    {
        $last = WorkflowExecution::query()
            ->where('workflow_definition_id', $definition->id)
            ->where('trigger_event', 'schedule')
            ->whereDate('started_at', today())
            ->exists();

        return ! $last;
    }
}
