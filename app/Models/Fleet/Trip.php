<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Trip extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'organization_id',
        'vehicle_id',
        'driver_id',
        'route_id',
        'start_location_id',
        'end_location_id',
        'planned_start_time',
        'planned_end_time',
        'started_at',
        'ended_at',
        'status',
        'distance_km',
        'duration_minutes',
        'notes',
    ];

    protected $casts = [
        'planned_start_time' => 'datetime',
        'planned_end_time' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'distance_km' => 'decimal:2',
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
     * @return BelongsTo<Route, $this>
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function startLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'start_location_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function endLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'end_location_id');
    }

    /**
     * @return HasMany<TripWaypoint, $this>
     */
    public function waypoints(): HasMany
    {
        return $this->hasMany(TripWaypoint::class)->orderBy('sequence');
    }

    /**
     * @return HasMany<BehaviorEvent, $this>
     */
    public function behaviorEvents(): HasMany
    {
        return $this->hasMany(BehaviorEvent::class);
    }
}
