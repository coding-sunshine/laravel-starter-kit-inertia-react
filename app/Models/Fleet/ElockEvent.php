<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Enums\Fleet\ElockEventType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $vehicle_id
 * @property string $event_type
 * @property \Carbon\Carbon $event_timestamp
 * @property float|null $lat
 * @property float|null $lng
 * @property string|null $device_id
 * @property array|null $metadata
 * @property bool $alert_sent
 */
class ElockEvent extends Model
{
    use BelongsToOrganization;

    protected $table = 'e_lock_events';

    protected $fillable = [
        'vehicle_id',
        'event_type',
        'event_timestamp',
        'lat',
        'lng',
        'device_id',
        'metadata',
        'alert_sent',
    ];

    protected $casts = [
        'event_timestamp' => 'datetime',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'metadata' => 'array',
        'alert_sent' => 'boolean',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function getEventTypeEnum(): ?ElockEventType
    {
        return ElockEventType::tryFrom($this->event_type);
    }
}
