<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'route_type',
        'description',
        'start_location_id',
        'end_location_id',
        'estimated_distance_km',
        'estimated_duration_minutes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'estimated_distance_km' => 'decimal:2',
    ];

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
     * @return HasMany<RouteStop, $this>
     */
    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
