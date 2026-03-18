<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SidingVehicleDispatch extends Model
{
    protected $table = 'siding_vehicle_dispatches';

    protected $fillable = [
        'siding_id',
        'serial_no',
        'ref_no',
        'permit_no',
        'pass_no',
        'stack_do_no',
        'issued_on',
        'truck_regd_no',
        'mineral',
        'mineral_type',
        'mineral_weight',
        'source',
        'destination',
        'consignee',
        'check_gate',
        'distance_km',
        'shift',
        'created_by',
    ];

    protected $casts = [
        'issued_on' => 'datetime',
        'mineral_weight' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Siding, $this>
     */
    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
