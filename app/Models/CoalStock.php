<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CoalStock extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'coal_stock';

    protected $fillable = [
        'siding_id',
        'opening_balance_mt',
        'receipt_quantity_mt',
        'dispatch_quantity_mt',
        'closing_balance_mt',
        'as_of_date',
        'remarks',
    ];

    protected $casts = [
        'as_of_date' => 'date',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }
}
