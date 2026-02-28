<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $vehicle_id
 * @property string $defect_number
 * @property string $title
 * @property string $description
 * @property string $category
 * @property string $severity
 * @property string $priority
 * @property int|null $reported_by_driver_id
 * @property int|null $reported_by_user_id
 * @property \Carbon\Carbon $reported_at
 * @property string|null $location_on_vehicle
 * @property string $status
 * @property int|null $work_order_id
 * @property bool $affects_roadworthiness
 * @property bool $affects_safety
 */
class Defect extends Model implements HasMedia
{
    use BelongsToOrganization;
    use InteractsWithMedia;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'vehicle_id',
        'defect_number',
        'title',
        'description',
        'category',
        'severity',
        'priority',
        'reported_by_driver_id',
        'reported_by_user_id',
        'reported_at',
        'location_on_vehicle',
        'status',
        'assigned_to',
        'work_order_id',
        'estimated_cost',
        'actual_cost',
        'affects_roadworthiness',
        'affects_safety',
        'vehicle_off_road_required',
        'temporary_fix_applied',
        'temporary_fix_description',
        'resolution_date',
        'resolution_description',
        'root_cause_analysis',
        'preventive_measures',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'resolution_date' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'affects_roadworthiness' => 'boolean',
        'affects_safety' => 'boolean',
        'vehicle_off_road_required' => 'boolean',
        'temporary_fix_applied' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Driver, $this>
     */
    public function reportedByDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'reported_by_driver_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reportedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
