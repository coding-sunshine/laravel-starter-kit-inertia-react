<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DispatchReport extends Model
{
    protected $table = 'dispatch_reports';

    protected $fillable = [
        'siding_id',
        'e_challan_no',
        'ref_no',
        'issued_on',
        'truck_no',
        'shift',
        'date',
        'trips',
        'wo_no',
        'transport_name',
        'mineral_wt',
        'gross_wt_siding_rec_wt',
        'tare_wt',
        'net_wt_siding_rec_wt',
        'tyres',
        'coal_ton_variation',
        'reached_datetime',
        'time_taken_trip',
        'remarks',
        'wb',
        'trip_id_no',
        'vehicle_dispatch_id',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function vehicleDispatch(): BelongsTo
    {
        return $this->belongsTo(VehicleDispatch::class, 'vehicle_dispatch_id');
    }

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'date' => 'date',
            'reached_datetime' => 'datetime',
            'mineral_wt' => 'decimal:2',
            'gross_wt_siding_rec_wt' => 'decimal:2',
            'tare_wt' => 'decimal:2',
            'net_wt_siding_rec_wt' => 'decimal:2',
            'coal_ton_variation' => 'decimal:2',
        ];
    }
}
