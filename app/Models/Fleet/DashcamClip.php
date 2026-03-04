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
 * @property int|null $incident_id
 * @property string|null $clip_id
 * @property string $event_type
 * @property string $status
 * @property string|null $clip_url
 * @property string|null $thumbnail_url
 * @property \Carbon\Carbon $recorded_at
 * @property int|null $duration_seconds
 * @property int|null $file_size_bytes
 * @property float|null $lat
 * @property float|null $lng
 * @property float|null $speed_kmh
 * @property array|null $metadata
 */
final class DashcamClip extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'incident_id',
        'clip_id',
        'event_type',
        'status',
        'clip_url',
        'thumbnail_url',
        'recorded_at',
        'duration_seconds',
        'file_size_bytes',
        'lat',
        'lng',
        'speed_kmh',
        'metadata',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'duration_seconds' => 'integer',
        'file_size_bytes' => 'integer',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'speed_kmh' => 'decimal:2',
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

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
