<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeofenceEvent extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'geofence_id',
        'vehicle_id',
        'driver_id',
        'trip_id',
        'event_type',
        'occurred_at',
        'lat',
        'lng',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Geofence, $this>
     */
    public function geofence(): BelongsTo
    {
        return $this->belongsTo(Geofence::class);
    }

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
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
