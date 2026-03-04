<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $vehicle_id
 * @property int|null $driver_id
 * @property int $charging_station_id
 * @property string|null $connector_id
 * @property string $session_id
 * @property \Carbon\Carbon $start_timestamp
 * @property \Carbon\Carbon|null $end_timestamp
 * @property int|null $duration_minutes
 * @property float|null $energy_delivered_kwh
 * @property float|null $charging_rate_kw
 * @property int|null $initial_soc_percent
 * @property int|null $final_soc_percent
 * @property float|null $cost
 * @property float|null $cost_per_kwh
 * @property string|null $payment_method
 * @property string $session_type
 * @property bool $interrupted
 * @property string|null $interruption_reason
 */
final class EvChargingSession extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'charging_station_id',
        'connector_id',
        'session_id',
        'start_timestamp',
        'end_timestamp',
        'duration_minutes',
        'energy_delivered_kwh',
        'charging_rate_kw',
        'initial_soc_percent',
        'final_soc_percent',
        'cost',
        'cost_per_kwh',
        'payment_method',
        'session_type',
        'interrupted',
        'interruption_reason',
    ];

    protected $casts = [
        'start_timestamp' => 'datetime',
        'end_timestamp' => 'datetime',
        'duration_minutes' => 'integer',
        'energy_delivered_kwh' => 'decimal:3',
        'charging_rate_kw' => 'decimal:2',
        'initial_soc_percent' => 'integer',
        'final_soc_percent' => 'integer',
        'cost' => 'decimal:2',
        'cost_per_kwh' => 'decimal:4',
        'interrupted' => 'boolean',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function chargingStation(): BelongsTo
    {
        return $this->belongsTo(EvChargingStation::class, 'charging_station_id');
    }
}
