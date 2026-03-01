<?php

declare(strict_types=1);

namespace App\Listeners\Fleet;

use App\Events\Fleet\AiJobCompleted;
use App\Jobs\Fleet\RunWorkflowExecutionJob;
use App\Models\Fleet\WorkflowDefinition;
use App\Models\Fleet\WorkflowExecution;

final class StartWorkflowsForAiJobCompleted
{
    public function handle(AiJobCompleted $event): void
    {
        $eventName = 'ai.' . $event->jobType . '.completed';

        $definitions = WorkflowDefinition::query()
            ->where('organization_id', $event->organizationId)
            ->where('is_active', true)
            ->where('trigger_type', 'event')
            ->get();

        foreach ($definitions as $definition) {
            $config = $definition->trigger_config ?? [];
            $configuredEvent = $config['event'] ?? null;
            if ($configuredEvent !== $eventName) {
                continue;
            }

            $execution = WorkflowExecution::create([
                'workflow_definition_id' => $definition->id,
                'started_at' => now(),
                'trigger_event' => $eventName,
                'trigger_entity_type' => $event->entityType,
                'trigger_entity_id' => $event->entityId,
                'trigger_data' => array_merge($event->resultSummary, ['job_type' => $event->jobType]),
                'status' => 'pending',
            ]);

            RunWorkflowExecutionJob::dispatch($execution->id);
        }
    }
}
