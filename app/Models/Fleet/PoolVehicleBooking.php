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
 * @property int $user_id
 * @property \Carbon\Carbon $booking_start
 * @property \Carbon\Carbon $booking_end
 * @property string $status
 * @property string|null $purpose
 * @property string|null $destination
 * @property int|null $odometer_start
 * @property int|null $odometer_end
 */
class PoolVehicleBooking extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'booking_start',
        'booking_end',
        'status',
        'purpose',
        'destination',
        'odometer_start',
        'odometer_end',
    ];

    protected $casts = [
        'booking_start' => 'datetime',
        'booking_end' => 'datetime',
        'odometer_start' => 'integer',
        'odometer_end' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
