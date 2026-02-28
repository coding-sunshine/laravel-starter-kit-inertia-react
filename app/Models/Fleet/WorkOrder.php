<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $vehicle_id
 * @property string $work_order_number
 * @property string $title
 * @property string|null $description
 * @property string $work_type
 * @property string $priority
 * @property string $status
 * @property string $urgency
 * @property int|null $assigned_garage_id
 * @property string|null $assigned_technician
 * @property \Carbon\Carbon|null $scheduled_date
 * @property \Carbon\Carbon|null $due_date
 * @property \Carbon\Carbon|null $completed_date
 * @property float|null $estimated_cost
 * @property float|null $total_cost
 * @property int|null $mileage_at_start
 * @property int|null $mileage_at_completion
 * @property bool $vehicle_off_road
 * @property \Carbon\Carbon|null $vor_start_time
 * @property \Carbon\Carbon|null $vor_end_time
 */
class WorkOrder extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'vehicle_id',
        'work_order_number',
        'title',
        'description',
        'work_type',
        'priority',
        'status',
        'urgency',
        'requested_by',
        'approved_by',
        'assigned_garage_id',
        'assigned_technician',
        'scheduled_date',
        'started_date',
        'completed_date',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'estimated_cost',
        'parts_cost',
        'labour_cost',
        'total_cost',
        'mileage_at_start',
        'mileage_at_completion',
        'vehicle_off_road',
        'vor_start_time',
        'vor_end_time',
        'warranty_applicable',
        'warranty_claim_number',
        'quality_check_passed',
        'customer_satisfaction_rating',
        'completion_notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'started_date' => 'date',
        'completed_date' => 'date',
        'due_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'labour_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'vehicle_off_road' => 'boolean',
        'vor_start_time' => 'datetime',
        'vor_end_time' => 'datetime',
        'warranty_applicable' => 'boolean',
        'quality_check_passed' => 'boolean',
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
    public function assignedGarage(): BelongsTo
    {
        return $this->belongsTo(Garage::class, 'assigned_garage_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<Defect, $this>
     */
    public function defects(): HasMany
    {
        return $this->hasMany(Defect::class, 'work_order_id');
    }
}
