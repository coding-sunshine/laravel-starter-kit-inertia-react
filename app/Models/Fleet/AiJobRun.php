<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $job_type
 * @property string $status
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 */
class AiJobRun extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'job_type', 'entity_type', 'entity_ids', 'parameters', 'status', 'priority',
        'scheduled_at', 'started_at', 'completed_at', 'worker_id', 'progress_percentage',
        'estimated_completion', 'cpu_time_seconds', 'memory_usage_mb',
        'retry_count', 'max_retries', 'error_message', 'error_code', 'result_data',
    ];

    protected $casts = [
        'entity_ids' => 'array',
        'parameters' => 'array',
        'result_data' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_completion' => 'datetime',
    ];
}
