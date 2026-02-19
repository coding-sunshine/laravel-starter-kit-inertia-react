<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SidingPerformance extends Model
{
    protected $fillable = [
        'siding_id',
        'as_of_date',
        'rakes_processed',
        'total_penalty_amount',
        'penalty_incidents',
        'average_demurrage_hours',
        'overload_incidents',
        'closing_stock_mt',
    ];

    protected $casts = [
        'as_of_date' => 'date',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }
}
