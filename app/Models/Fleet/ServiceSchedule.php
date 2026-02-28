<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $vehicle_id
 * @property string $service_type
 * @property string $interval_type
 * @property int $interval_value
 * @property string $interval_unit
 * @property \Carbon\Carbon|null $last_service_date
 * @property int|null $last_service_mileage
 * @property \Carbon\Carbon|null $next_service_due_date
 * @property int|null $next_service_due_mileage
 * @property int $alert_days_before
 * @property int $alert_km_before
 * @property int|null $preferred_garage_id
 * @property float|null $estimated_cost
 * @property bool $is_mandatory
 * @property bool $is_active
 */
class ServiceSchedule extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'vehicle_id',
        'service_type',
        'interval_type',
        'interval_value',
        'interval_unit',
        'last_service_date',
        'last_service_mileage',
        'next_service_due_date',
        'next_service_due_mileage',
        'alert_days_before',
        'alert_km_before',
        'preferred_garage_id',
        'estimated_cost',
        'is_mandatory',
        'is_active',
    ];

    protected $casts = [
        'last_service_date' => 'date',
        'next_service_due_date' => 'date',
        'alert_days_before' => 'integer',
        'alert_km_before' => 'integer',
        'estimated_cost' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Garage, $this>
     */
    public function preferredGarage(): BelongsTo
    {
        return $this->belongsTo(Garage::class, 'preferred_garage_id');
    }
}
