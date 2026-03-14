<?php

declare(strict_types=1);

namespace App\Workflows;

use Generator;
use Workflow\Workflow;

use function Workflow\activity;

/**
 * Durable workflow that runs a multi-day nurture sequence for an enrolled contact.
 * Uses laravel-workflow for crash-safe, resumable execution.
 *
 * Start with:
 * WorkflowStub::make(NurtureSequenceWorkflow::class)->start($enrollmentId);
 */
final class NurtureSequenceWorkflow extends Workflow
{
    public function execute(int $enrollmentId): Generator
    {
        return yield activity(SendNurtureStepActivity::class, $enrollmentId);
    }
}
