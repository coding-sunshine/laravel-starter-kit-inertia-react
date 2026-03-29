<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * Derive shift from issued_on timestamp: 1st (00:00-08:00), 2nd (08:01-16:00), 3rd (16:01-23:59).
     */
    public static function shiftFromIssuedOn(?DateTimeInterface $issuedOn): ?string
    {
        if (! $issuedOn) {
            return null;
        }

        $h = (int) $issuedOn->format('G');
        $m = (int) $issuedOn->format('i');
        $minutes = $h * 60 + $m;

        if ($minutes <= 480) { // 00:00-08:00
            return '1st';
        }
        if ($minutes <= 960) { // 08:01-16:00
            return '2nd';
        }

        // 16:01-23:59
        return '3rd';
    }

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dispatchReport(): HasOne
    {
        return $this->hasOne(DispatchReport::class, 'vehicle_dispatch_id');
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
        if (! $shift || $shift === 'all' || ! in_array($shift, ['1st', '2nd', '3rd'], true)) {
            return $query;
        }

        return $query->whereNotNull('issued_on')->where(function ($q) use ($shift): void {
            $driver = $q->getConnection()->getDriverName();
            if ($driver === 'sqlite') {
                if ($shift === '1st') {
                    $q->whereRaw("time(issued_on) >= '00:00:00' AND time(issued_on) <= '08:00:00'");
                } elseif ($shift === '2nd') {
                    $q->whereRaw("time(issued_on) >= '08:01:00' AND time(issued_on) <= '16:00:00'");
                } else {
                    $q->whereRaw("time(issued_on) >= '16:01:00'");
                }
            } else {
                if ($shift === '1st') {
                    $q->whereRaw('TIME(issued_on) >= ? AND TIME(issued_on) <= ?', ['00:00:00', '08:00:00']);
                } elseif ($shift === '2nd') {
                    $q->whereRaw('TIME(issued_on) > ? AND TIME(issued_on) <= ?', ['08:00:00', '16:00:00']);
                } else {
                    $q->whereRaw('TIME(issued_on) > ?', ['16:00:00']);
                }
            }
        });
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
