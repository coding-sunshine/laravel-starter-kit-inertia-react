<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RakeCharge extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'rake_id',
        'charge_type',
        'amount',
        'data_source',
        'is_actual_charges',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_actual_charges' => 'boolean',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }
}
