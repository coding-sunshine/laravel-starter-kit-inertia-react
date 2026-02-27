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
 * @property int|null $location_id
 * @property string $name
 * @property string|null $description
 * @property string $geofence_type
 * @property float|null $center_lat
 * @property float|null $center_lng
 * @property int|null $radius_meters
 * @property array|null $polygon_coordinates
 * @property string|null $location_type
 * @property bool $alert_on_entry
 * @property bool $alert_on_exit
 * @property bool $alert_on_speeding
 * @property int|null $speed_limit_kmh
 * @property bool $time_restrictions_apply
 * @property string|null $allowed_hours_start
 * @property string|null $allowed_hours_end
 * @property array|null $allowed_days
 * @property bool $is_active
 * @property \Carbon\Carbon|null $monitoring_start_date
 * @property \Carbon\Carbon|null $monitoring_end_date
 * @property int $total_entries
 * @property int $total_exits
 * @property int $total_violations
 * @property \Carbon\Carbon|null $last_activity_at
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Carbon\Carbon|null $deleted_at
 */
class Geofence extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'location_id',
        'name',
        'description',
        'geofence_type',
        'center_lat',
        'center_lng',
        'radius_meters',
        'polygon_coordinates',
        'location_type',
        'alert_on_entry',
        'alert_on_exit',
        'alert_on_speeding',
        'speed_limit_kmh',
        'time_restrictions_apply',
        'allowed_hours_start',
        'allowed_hours_end',
        'allowed_days',
        'is_active',
        'monitoring_start_date',
        'monitoring_end_date',
        'total_entries',
        'total_exits',
        'total_violations',
        'last_activity_at',
        'notes',
    ];

    protected $casts = [
        'polygon_coordinates' => 'array',
        'allowed_days' => 'array',
        'alert_on_entry' => 'boolean',
        'alert_on_exit' => 'boolean',
        'alert_on_speeding' => 'boolean',
        'time_restrictions_apply' => 'boolean',
        'is_active' => 'boolean',
        'monitoring_start_date' => 'date',
        'monitoring_end_date' => 'date',
        'last_activity_at' => 'datetime',
        'center_lat' => 'decimal:8',
        'center_lng' => 'decimal:8',
    ];

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
