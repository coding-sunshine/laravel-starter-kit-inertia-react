<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class HistoricalMine extends Model
{
    protected $fillable = [
        'month',
        'trips_dispatched',
        'dispatched_qty',
        'trips_received',
        'received_qty',
        'coal_production_qty',
        'ob_production_qty',
    ];

    protected $casts = [
        'month' => 'date',
        'trips_dispatched' => 'integer',
        'dispatched_qty' => 'decimal:2',
        'trips_received' => 'integer',
        'received_qty' => 'decimal:2',
        'coal_production_qty' => 'decimal:2',
        'ob_production_qty' => 'decimal:2',
    ];
}
