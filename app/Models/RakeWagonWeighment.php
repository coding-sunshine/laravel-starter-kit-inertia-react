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
        'wagon_number',
        'wagon_sequence',
        'wagon_type',
        'axles',
        'cc_capacity_mt',
        'printed_tare_mt',
        'actual_gross_mt',
        'actual_tare_mt',
        'net_weight_mt',
        'under_load_mt',
        'over_load_mt',
        'speed_kmph',
        'weighment_time',
        'slip_number',
        'action_taken',
    ];

    protected $casts = [
        'cc_capacity_mt' => 'decimal:2',
        'printed_tare_mt' => 'decimal:2',
        'actual_gross_mt' => 'decimal:2',
        'actual_tare_mt' => 'decimal:2',
        'net_weight_mt' => 'decimal:2',
        'under_load_mt' => 'decimal:2',
        'over_load_mt' => 'decimal:2',
        'speed_kmph' => 'decimal:2',
        'weighment_time' => 'datetime',
    ];

    public function rakeWeighment(): BelongsTo
    {
        return $this->belongsTo(RakeWeighment::class);
    }

    public function wagon(): BelongsTo
    {
        return $this->belongsTo(Wagon::class);
    }
}
