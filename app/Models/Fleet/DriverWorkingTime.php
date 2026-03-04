<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $driver_id
 * @property \Carbon\Carbon $date
 * @property \Carbon\Carbon|null $shift_start_time
 * @property \Carbon\Carbon|null $shift_end_time
 * @property int $break_time_minutes
 * @property int $driving_time_minutes
 * @property int $other_work_time_minutes
 * @property int $total_duty_time_minutes
 * @property bool $wtd_compliant
 * @property bool $rtd_compliant
 * @property bool $manual_entry
 */
final class DriverWorkingTime extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'driver_working_time';

    protected $fillable = [
        'driver_id',
        'date',
        'shift_start_time',
        'shift_end_time',
        'break_time_minutes',
        'driving_time_minutes',
        'other_work_time_minutes',
        'available_time_minutes',
        'rest_time_minutes',
        'total_duty_time_minutes',
        'weekly_driving_time_minutes',
        'fortnightly_driving_time_minutes',
        'wtd_compliant',
        'rtd_compliant',
        'violations',
        'tachograph_data',
        'manual_entry',
        'verified_by',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'shift_start_time' => 'datetime',
        'shift_end_time' => 'datetime',
        'break_time_minutes' => 'integer',
        'driving_time_minutes' => 'integer',
        'other_work_time_minutes' => 'integer',
        'available_time_minutes' => 'integer',
        'rest_time_minutes' => 'integer',
        'total_duty_time_minutes' => 'integer',
        'weekly_driving_time_minutes' => 'integer',
        'fortnightly_driving_time_minutes' => 'integer',
        'wtd_compliant' => 'boolean',
        'rtd_compliant' => 'boolean',
        'violations' => 'array',
        'tachograph_data' => 'array',
        'manual_entry' => 'boolean',
    ];

    /**
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
