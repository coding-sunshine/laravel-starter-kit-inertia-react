<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RakeWagonWeighment extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

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

    /**
     * Overload in MT derived from the weighbridge numbers rather than trusting
     * the stored `over_load_mt` column.
     *
     * Imported rows exist where the column is decimal-shifted (over_load_mt
     * 99.00 against net 65.99 and CC 65.00, i.e. a true overload of 0.99 MT),
     * which inflated POL1 penalties by two orders of magnitude. Falls back to
     * the wagon's PCC for capacity, then to the stored column, and never
     * returns a negative value.
     */
    public function effectiveOverloadMt(): float
    {
        $net = $this->net_weight_mt !== null ? (float) $this->net_weight_mt : null;
        $capacity = $this->cc_capacity_mt !== null
            ? (float) $this->cc_capacity_mt
            : ($this->wagon?->pcc_weight_mt !== null ? (float) $this->wagon->pcc_weight_mt : null);

        if ($net !== null && $capacity !== null && $capacity > 0.0) {
            return round(max(0.0, $net - $capacity), 2);
        }

        return round(max(0.0, (float) ($this->over_load_mt ?? 0.0)), 2);
    }

    public function rakeWeighment(): BelongsTo
    {
        return $this->belongsTo(RakeWeighment::class);
    }

    public function wagon(): BelongsTo
    {
        return $this->belongsTo(Wagon::class);
    }
}
