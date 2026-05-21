<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Daily aggregates from {@see SidingVehicleDispatch} keyed by calendar {@see issued_on}, siding, and numeric shift tier.
 *
 * @property-read string $issued_on_date
 */
final class DailySidingVehicleDispatchRollup extends Model
{
    protected $table = 'daily_siding_vehicle_dispatch_rollups';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'issued_on_date',
        'siding_id',
        'shift_number',
        'dispatches_count',
        'qty_mineral_mt',
    ];

    protected $casts = [
        'issued_on_date' => 'date',
        'shift_number' => 'integer',
        'dispatches_count' => 'integer',
        'qty_mineral_mt' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Siding, $this>
     */
    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }
}
