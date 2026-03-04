<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property \Carbon\Carbon $recorded_at
 * @property int $soc_percent
 * @property int|null $soh_percent
 * @property float|null $voltage
 * @property float|null $current_amps
 * @property int|null $temperature_celsius
 * @property int|null $range_remaining_km
 * @property float|null $energy_consumed_kwh
 * @property float|null $regenerative_energy_kwh
 * @property string $charging_status
 * @property array|null $battery_warnings
 */
final class EvBatteryData extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'ev_battery_data';

    protected $fillable = [
        'vehicle_id',
        'recorded_at',
        'soc_percent',
        'soh_percent',
        'voltage',
        'current_amps',
        'temperature_celsius',
        'range_remaining_km',
        'energy_consumed_kwh',
        'regenerative_energy_kwh',
        'charging_status',
        'battery_warnings',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'soc_percent' => 'integer',
        'soh_percent' => 'integer',
        'voltage' => 'decimal:2',
        'current_amps' => 'decimal:2',
        'temperature_celsius' => 'integer',
        'range_remaining_km' => 'integer',
        'energy_consumed_kwh' => 'decimal:3',
        'regenerative_energy_kwh' => 'decimal:3',
        'battery_warnings' => 'array',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
