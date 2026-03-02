<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class VehicleDispatch extends Model
{
    use HasFactory;

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
        'issued_on' => 'timestamp',
        'mineral_weight' => 'decimal:2',
        'distance_km' => 'integer',
        'serial_no' => 'integer',
        'ref_no' => 'integer',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForSiding($query, $sidingId)
    {
        return $query->where('siding_id', $sidingId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('issued_on', $date);
    }

    public function scopeForShift($query, $shift)
    {
        return $query->where('shift', $shift);
    }

    public function scopeByPermitNo($query, $permitNo)
    {
        return $query->where('permit_no', 'like', "%{$permitNo}%");
    }

    public function scopeByTruckRegdNo($query, $truckRegdNo)
    {
        return $query->where('truck_regd_no', 'like', "%{$truckRegdNo}%");
    }
}
