<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $report_id
 * @property \Carbon\Carbon $execution_start
 * @property \Carbon\Carbon|null $execution_end
 * @property string $status
 * @property string $triggered_by
 * @property int|null $triggered_by_user_id
 * @property array|null $parameters_used
 * @property array|null $filters_used
 * @property int|null $record_count
 * @property int|null $file_size_bytes
 * @property string|null $file_path
 * @property string|null $error_message
 * @property int|null $execution_time_seconds
 */
final class ReportExecution extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'report_id',
        'execution_start',
        'execution_end',
        'status',
        'triggered_by',
        'triggered_by_user_id',
        'parameters_used',
        'filters_used',
        'record_count',
        'file_size_bytes',
        'file_path',
        'error_message',
        'execution_time_seconds',
    ];

    protected $casts = [
        'execution_start' => 'datetime',
        'execution_end' => 'datetime',
        'parameters_used' => 'array',
        'filters_used' => 'array',
        'record_count' => 'integer',
        'file_size_bytes' => 'integer',
        'execution_time_seconds' => 'integer',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
