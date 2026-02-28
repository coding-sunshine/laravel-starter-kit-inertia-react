<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $description
 * @property string $report_type
 * @property array|null $parameters
 * @property array|null $filters
 * @property bool $schedule_enabled
 * @property string $schedule_frequency
 * @property int|null $schedule_day_of_week
 * @property int|null $schedule_day_of_month
 * @property \Carbon\Carbon|null $next_run_date
 * @property array|null $recipients
 * @property string $format
 * @property bool $is_active
 */
class Report extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'name',
        'description',
        'report_type',
        'parameters',
        'filters',
        'schedule_enabled',
        'schedule_frequency',
        'schedule_day_of_week',
        'schedule_day_of_month',
        'next_run_date',
        'recipients',
        'format',
        'is_active',
    ];

    protected $casts = [
        'parameters' => 'array',
        'filters' => 'array',
        'schedule_enabled' => 'boolean',
        'schedule_day_of_week' => 'integer',
        'schedule_day_of_month' => 'integer',
        'next_run_date' => 'date',
        'recipients' => 'array',
        'is_active' => 'boolean',
    ];

    public function reportExecutions(): HasMany
    {
        return $this->hasMany(ReportExecution::class, 'report_id');
    }
}
