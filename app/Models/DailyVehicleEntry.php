<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class DailyVehicleEntry extends Model
{
    protected $table = 'daily_vehicle_entries';

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'siding_id',
        'entry_date',
        'shift',
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
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'entry_date' => 'date',
        'reached_at' => 'datetime',
        'gross_wt' => 'decimal:2',
        'tare_wt' => 'decimal:2',
        'tare_wt_two' => 'decimal:2',
        'shift' => 'integer',
        'vehicle_no' => 'string',
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
