<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RouteStop extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'route_id',
        'location_id',
        'name',
        'sort_order',
        'planned_arrival_time',
        'planned_departure_time',
        'actual_arrival_time',
        'actual_departure_time',
        'notes',
    ];

    protected $casts = [
        'planned_arrival_time' => 'datetime',
        'planned_departure_time' => 'datetime',
        'actual_arrival_time' => 'datetime',
        'actual_departure_time' => 'datetime',
    ];

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
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
