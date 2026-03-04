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
 * @property int $driver_id
 * @property int $vehicle_id
 * @property string $assignment_type
 * @property \Carbon\Carbon $assigned_date
 * @property \Carbon\Carbon|null $unassigned_date
 * @property bool $is_current
 * @property string|null $notes
 * @property int $assigned_by
 */
final class DriverVehicleAssignment extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'assignment_type',
        'assigned_date',
        'unassigned_date',
        'is_current',
        'notes',
        'assigned_by',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'unassigned_date' => 'date',
        'is_current' => 'boolean',
    ];

    /**
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
