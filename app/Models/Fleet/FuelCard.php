<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $card_number
 * @property string $provider
 * @property string $card_type
 * @property string $status
 * @property \Carbon\Carbon|null $issue_date
 * @property \Carbon\Carbon|null $expiry_date
 * @property bool $pin_required
 * @property float|null $daily_limit
 * @property float|null $weekly_limit
 * @property float|null $monthly_limit
 * @property float|null $transaction_limit
 * @property array|null $fuel_type_restrictions
 * @property array|null $location_restrictions
 * @property array|null $time_restrictions
 * @property int|null $assigned_vehicle_id
 * @property int|null $assigned_driver_id
 */
final class FuelCard extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'card_number',
        'provider',
        'card_type',
        'status',
        'issue_date',
        'expiry_date',
        'pin_required',
        'daily_limit',
        'weekly_limit',
        'monthly_limit',
        'transaction_limit',
        'fuel_type_restrictions',
        'location_restrictions',
        'time_restrictions',
        'assigned_vehicle_id',
        'assigned_driver_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'pin_required' => 'boolean',
        'daily_limit' => 'decimal:2',
        'weekly_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
        'transaction_limit' => 'decimal:2',
        'fuel_type_restrictions' => 'array',
        'location_restrictions' => 'array',
        'time_restrictions' => 'array',
    ];

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function assignedVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'assigned_vehicle_id');
    }

    /**
     * @return BelongsTo<Driver, $this>
     */
    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'assigned_driver_id');
    }

    /**
     * @return HasMany<FuelTransaction, $this>
     */
    public function fuelTransactions(): HasMany
    {
        return $this->hasMany(FuelTransaction::class, 'fuel_card_id');
    }
}
