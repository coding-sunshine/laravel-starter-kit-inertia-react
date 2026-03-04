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
 * @property string $registration
 * @property string|null $vin
 * @property string|null $fleet_number
 * @property string $make
 * @property string $model
 * @property int|null $year
 * @property string $fuel_type
 * @property string $vehicle_type
 * @property int|null $weight_kg
 * @property int|null $max_payload_kg
 * @property int|null $seating_capacity
 * @property string $status
 * @property int|null $current_driver_id
 * @property int|null $home_location_id
 * @property float|null $current_lat
 * @property float|null $current_lng
 * @property \Carbon\Carbon|null $location_updated_at
 * @property int $odometer_reading
 * @property \Carbon\Carbon|null $odometer_updated_at
 * @property int $monthly_distance_km
 * @property float $monthly_fuel_cost
 * @property \Carbon\Carbon|null $purchase_date
 * @property float|null $purchase_price
 * @property float|null $current_value
 * @property float|null $depreciation_rate
 * @property string|null $insurance_group
 * @property \Carbon\Carbon|null $mot_expiry_date
 * @property \Carbon\Carbon|null $insurance_expiry_date
 * @property \Carbon\Carbon|null $tax_expiry_date
 * @property string $compliance_status
 * @property int|null $co2_emissions
 * @property string|null $euro_standard
 * @property float $maintenance_risk_score
 * @property float $efficiency_score
 * @property float $safety_score
 * @property \Carbon\Carbon|null $scores_updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Vehicle extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'registration',
        'vin',
        'fleet_number',
        'make',
        'model',
        'year',
        'fuel_type',
        'vehicle_type',
        'weight_kg',
        'max_payload_kg',
        'seating_capacity',
        'status',
        'current_driver_id',
        'home_location_id',
        'current_lat',
        'current_lng',
        'location_updated_at',
        'odometer_reading',
        'odometer_updated_at',
        'monthly_distance_km',
        'monthly_fuel_cost',
        'purchase_date',
        'purchase_price',
        'current_value',
        'depreciation_rate',
        'insurance_group',
        'mot_expiry_date',
        'insurance_expiry_date',
        'tax_expiry_date',
        'compliance_status',
        'co2_emissions',
        'euro_standard',
        'maintenance_risk_score',
        'efficiency_score',
        'safety_score',
        'scores_updated_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'weight_kg' => 'integer',
        'max_payload_kg' => 'integer',
        'seating_capacity' => 'integer',
        'odometer_reading' => 'integer',
        'monthly_distance_km' => 'integer',
        'purchase_date' => 'date',
        'mot_expiry_date' => 'date',
        'insurance_expiry_date' => 'date',
        'tax_expiry_date' => 'date',
        'location_updated_at' => 'datetime',
        'odometer_updated_at' => 'datetime',
        'scores_updated_at' => 'datetime',
        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:4',
        'monthly_fuel_cost' => 'decimal:2',
        'current_lat' => 'decimal:8',
        'current_lng' => 'decimal:8',
        'maintenance_risk_score' => 'decimal:2',
        'efficiency_score' => 'decimal:2',
        'safety_score' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Driver, $this>
     */
    public function currentDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'current_driver_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function homeLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'home_location_id');
    }

    /**
     * @return HasMany<DriverVehicleAssignment, $this>
     */
    public function driverAssignments(): HasMany
    {
        return $this->hasMany(DriverVehicleAssignment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<DriverVehicleAssignment, $this>
     */
    public function currentAssignment()
    {
        return $this->hasOne(DriverVehicleAssignment::class)->where('is_current', true);
    }
}
