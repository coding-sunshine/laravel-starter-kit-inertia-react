<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripWaypoint extends Model
{
    protected $fillable = [
        'trip_id',
        'lat',
        'lng',
        'recorded_at',
        'sequence',
        'speed_kmh',
        'heading',
        'altitude_m',
        'accuracy_m',
        'raw_payload',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
