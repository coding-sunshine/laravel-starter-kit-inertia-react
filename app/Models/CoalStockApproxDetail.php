<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CoalStockApproxDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'siding_id',
        'date',
        'railway_siding_opening_coal_stock',
        'railway_siding_closing_coal_stock',
        'coal_dispatch_qty',
        'no_of_rakes',
        'rakes_qty',
        'source',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
        'railway_siding_opening_coal_stock' => 'decimal:2',
        'railway_siding_closing_coal_stock' => 'decimal:2',
        'coal_dispatch_qty' => 'decimal:2',
        'rakes_qty' => 'decimal:2',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }
}
