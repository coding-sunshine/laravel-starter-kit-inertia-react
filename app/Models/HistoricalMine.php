<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HistoricalMine extends Model
{
    protected $fillable = [
        'month',
        'siding_id',
        'trips_dispatched',
        'dispatched_qty',
        'trips_received',
        'received_qty',
        'coal_production_qty',
        'ob_production_qty',
        'remarks',
    ];

    protected $casts = [
        'month' => 'date',
        'siding_id' => 'integer',
        'trips_dispatched' => 'integer',
        'dispatched_qty' => 'decimal:2',
        'trips_received' => 'integer',
        'received_qty' => 'decimal:2',
        'coal_production_qty' => 'decimal:2',
        'ob_production_qty' => 'decimal:2',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }
}
