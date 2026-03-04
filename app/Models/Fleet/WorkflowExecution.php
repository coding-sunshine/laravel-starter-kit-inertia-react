<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $workflow_definition_id
 * @property \Carbon\Carbon $started_at
 * @property string $status
 */
final class WorkflowExecution extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'workflow_definition_id', 'started_at', 'completed_at',
        'trigger_event', 'trigger_entity_type', 'trigger_entity_id', 'trigger_data',
        'status', 'steps_attempted', 'steps_completed', 'steps_failed',
        'error_message', 'error_details', 'result_data',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'trigger_data' => 'array',
        'error_details' => 'array',
        'result_data' => 'array',
    ];

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }
}
