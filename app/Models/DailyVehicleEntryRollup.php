<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Daily aggregates for {@see DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH} keyed by {@see rollup_day}, siding, and shift.
 *
 * @property-read string $rollup_day
 */
final class DailyVehicleEntryRollup extends Model
{
    protected $table = 'daily_vehicle_entry_rollups';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'rollup_day',
        'siding_id',
        'shift',
        'entries_count',
        'completed_entries_count',
        'pending_entries_count',
        'completed_net_wt_mt',
        'pending_gross_wt_mt',
    ];

    protected $casts = [
        'rollup_day' => 'date',
        'shift' => 'integer',
        'entries_count' => 'integer',
        'completed_entries_count' => 'integer',
        'pending_entries_count' => 'integer',
        'completed_net_wt_mt' => 'decimal:2',
        'pending_gross_wt_mt' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Siding, $this>
     */
    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }
}
