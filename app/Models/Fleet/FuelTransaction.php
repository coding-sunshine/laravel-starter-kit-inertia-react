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
 * @property int $vehicle_id
 * @property int|null $driver_id
 * @property int $fuel_card_id
 * @property string|null $external_transaction_id
 * @property \Carbon\Carbon $transaction_timestamp
 * @property int|null $fuel_station_id
 * @property string|null $fuel_station_name
 * @property string|null $fuel_station_address
 * @property float|null $lat
 * @property float|null $lng
 * @property string $fuel_type
 * @property float|null $litres
 * @property float $price_per_litre
 * @property float $total_cost
 * @property float|null $vat_amount
 * @property int|null $odometer_reading
 * @property string|null $pump_number
 * @property string|null $receipt_number
 * @property string|null $authorization_code
 * @property string|null $transaction_method
 * @property string $validation_status
 * @property int|null $validated_by
 */
final class FuelTransaction extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'fuel_card_id',
        'external_transaction_id',
        'transaction_timestamp',
        'fuel_station_id',
        'fuel_station_name',
        'fuel_station_address',
        'lat',
        'lng',
        'fuel_type',
        'litres',
        'price_per_litre',
        'total_cost',
        'vat_amount',
        'odometer_reading',
        'pump_number',
        'receipt_number',
        'authorization_code',
        'transaction_method',
        'fuel_efficiency_kmpl',
        'distance_since_last_fill',
        'tank_capacity_percent',
        'fraud_risk_score',
        'anomaly_flags',
        'validation_status',
        'validated_by',
        'validation_notes',
    ];

    protected $casts = [
        'transaction_timestamp' => 'datetime',
        'litres' => 'decimal:3',
        'price_per_litre' => 'decimal:3',
        'total_cost' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'lat' => 'decimal:6',
        'lng' => 'decimal:6',
        'fuel_efficiency_kmpl' => 'decimal:3',
        'distance_since_last_fill' => 'decimal:2',
        'tank_capacity_percent' => 'decimal:2',
        'fraud_risk_score' => 'decimal:2',
        'anomaly_flags' => 'array',
    ];

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
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * @return BelongsTo<FuelCard, $this>
     */
    public function fuelCard(): BelongsTo
    {
        return $this->belongsTo(FuelCard::class);
    }

    /**
     * @return BelongsTo<FuelStation, $this>
     */
    public function fuelStation(): BelongsTo
    {
        return $this->belongsTo(FuelStation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
