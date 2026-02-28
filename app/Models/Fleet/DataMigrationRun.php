<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Enums\Fleet\DataMigrationRunStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property int|null $triggered_by
 * @property string|null $batch_id
 * @property string $migration_type
 * @property string $status
 * @property int $total_records
 * @property int $processed_records
 * @property int $failed_records
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property string|null $error_summary
 * @property array|null $error_log
 */
class DataMigrationRun extends Model
{
    protected $fillable = [
        'organization_id',
        'triggered_by',
        'batch_id',
        'migration_type',
        'status',
        'total_records',
        'processed_records',
        'failed_records',
        'started_at',
        'completed_at',
        'error_summary',
        'error_log',
    ];

    protected $casts = [
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'failed_records' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'error_log' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function getStatusEnum(): ?DataMigrationRunStatus
    {
        return DataMigrationRunStatus::tryFrom($this->status);
    }
}
