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
 * @property int|null $trip_id
 * @property \Carbon\Carbon $recorded_at
 * @property array|null $axle_weights_kg
 * @property float|null $total_weight_kg
 * @property bool $overload_flag
 * @property float|null $legal_limit_kg
 * @property array|null $metadata
 */
final class AxleLoadReading extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'vehicle_id',
        'trip_id',
        'recorded_at',
        'axle_weights_kg',
        'total_weight_kg',
        'overload_flag',
        'legal_limit_kg',
        'metadata',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'axle_weights_kg' => 'array',
        'total_weight_kg' => 'decimal:2',
        'overload_flag' => 'boolean',
        'legal_limit_kg' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
