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
 * @property int $grey_fleet_vehicle_id
 * @property int $user_id
 * @property \Carbon\Carbon $claim_date
 * @property int|null $start_odometer
 * @property int|null $end_odometer
 * @property int|null $distance_km
 * @property string|null $purpose
 * @property string|null $destination
 * @property float|null $amount_claimed
 * @property float|null $amount_approved
 * @property string $status
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property string|null $rejection_reason
 */
final class MileageClaim extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'grey_fleet_vehicle_id',
        'user_id',
        'claim_date',
        'start_odometer',
        'end_odometer',
        'distance_km',
        'purpose',
        'destination',
        'amount_claimed',
        'amount_approved',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'start_odometer' => 'integer',
        'end_odometer' => 'integer',
        'distance_km' => 'integer',
        'amount_claimed' => 'decimal:2',
        'amount_approved' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function greyFleetVehicle(): BelongsTo
    {
        return $this->belongsTo(GreyFleetVehicle::class, 'grey_fleet_vehicle_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
