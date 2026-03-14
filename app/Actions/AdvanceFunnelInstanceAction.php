<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FunnelInstance;

/**
 * Advance a funnel instance to the next step.
 */
final readonly class AdvanceFunnelInstanceAction
{
    public function handle(FunnelInstance $instance): FunnelInstance
    {
        $instance->load('template');

        $template = $instance->template;
        $config = $template?->config ?? [];
        $emailSequences = $config['email_sequences'] ?? [];
        $totalSteps = count($emailSequences);

        $instance->current_step = $instance->current_step + 1;

        if ($totalSteps > 0 && $instance->current_step >= $totalSteps) {
            $instance->status = 'completed';
            $instance->completed_at = now();
        }

        $instance->save();

        return $instance;
    }
}
