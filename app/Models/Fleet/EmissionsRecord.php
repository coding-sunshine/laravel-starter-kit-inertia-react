<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $vehicle_id
 * @property int|null $driver_id
 * @property int|null $trip_id
 * @property string $scope
 * @property string $emissions_type
 * @property \Carbon\Carbon $record_date
 * @property float $co2_kg
 * @property float|null $fuel_consumed_litres
 * @property float|null $distance_km
 */
final class EmissionsRecord extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'vehicle_id', 'driver_id', 'trip_id', 'scope', 'emissions_type',
        'record_date', 'co2_kg', 'fuel_consumed_litres', 'distance_km', 'metadata',
    ];

    protected $casts = [
        'record_date' => 'date',
        'co2_kg' => 'decimal:3',
        'fuel_consumed_litres' => 'decimal:3',
        'distance_km' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
