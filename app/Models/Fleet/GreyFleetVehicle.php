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
 * @property int|null $user_id
 * @property int|null $driver_id
 * @property string|null $registration
 * @property string|null $make
 * @property string|null $model
 * @property int|null $year
 * @property string|null $colour
 * @property string|null $fuel_type
 * @property int|null $engine_cc
 * @property bool $is_approved
 * @property \Carbon\Carbon|null $approval_date
 * @property string|null $notes
 * @property bool $is_active
 */
final class GreyFleetVehicle extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'user_id',
        'driver_id',
        'registration',
        'make',
        'model',
        'year',
        'colour',
        'fuel_type',
        'engine_cc',
        'is_approved',
        'approval_date',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'year' => 'integer',
        'engine_cc' => 'integer',
        'is_approved' => 'boolean',
        'approval_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function mileageClaims(): HasMany
    {
        return $this->hasMany(MileageClaim::class, 'grey_fleet_vehicle_id');
    }
}
