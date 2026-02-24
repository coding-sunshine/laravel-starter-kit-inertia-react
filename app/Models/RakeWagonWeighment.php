<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RakeWagonWeighment extends Model
{
    protected $fillable = [
        'rake_weighment_id',
        'wagon_id',
        'gross_weight_mt',
        'is_overloaded',
    ];

    protected $casts = [
        'gross_weight_mt' => 'decimal:2',
        'is_overloaded' => 'boolean',
    ];

    public function weighment(): BelongsTo
    {
        return $this->belongsTo(Weighment::class);
    }

    public function wagon(): BelongsTo
    {
        return $this->belongsTo(Wagon::class);
    }

    public function rakeWagonLoading(): BelongsTo
    {
        return $this->belongsTo(RakeWagonLoading::class);
    }
}
