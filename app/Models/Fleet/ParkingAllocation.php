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
 * @property int $vehicle_id
 * @property int $location_id
 * @property \Carbon\Carbon $allocated_from
 * @property \Carbon\Carbon|null $allocated_to
 * @property string|null $spot_identifier
 * @property float|null $cost
 */
class ParkingAllocation extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'vehicle_id',
        'location_id',
        'allocated_from',
        'allocated_to',
        'spot_identifier',
        'cost',
    ];

    protected $casts = [
        'allocated_from' => 'datetime',
        'allocated_to' => 'datetime',
        'cost' => 'decimal:2',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
