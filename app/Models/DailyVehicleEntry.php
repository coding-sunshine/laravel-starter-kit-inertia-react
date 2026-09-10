<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class DailyVehicleEntry extends Model
{
    /**
     * Mass assignable attributes.
     */
    public const ENTRY_TYPE_ROAD_DISPATCH = 'road_dispatch';

    public const ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT = 'railway_siding_empty_weighment';

    protected $table = 'daily_vehicle_entries';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'challan_mode' => 'online',
    ];

    protected $fillable = [
        'siding_id',
        'entry_date',
        'shift',
        'entry_type',
        'e_challan_no',
        'vehicle_no',
        'gross_wt',
        'tare_wt',
        'tare_wt_two',
        'reached_at',
        'wb_no',
        'd_challan_no',
        'challan_mode',
        'status',
        'created_by',
        'updated_by',
        'trip_id_no',
        'transport_name',
        'remarks',
        'net_wt',
        'inline_submitted_at',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'entry_date' => 'date',
        'inline_submitted_at' => 'datetime',
        'reached_at' => 'datetime',
        'gross_wt' => 'decimal:2',
        'tare_wt' => 'decimal:2',
        'tare_wt_two' => 'decimal:2',
        'net_wt' => 'decimal:2',
        'shift' => 'integer',
        'vehicle_no' => 'string',
        'entry_type' => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Stock ledger receipt created when this entry is completed.
     *
     * @return HasOne<StockLedger, $this>
     */
    public function stockLedger(): HasOne
    {
        return $this->hasOne(StockLedger::class, 'daily_vehicle_entry_id');
    }
}
