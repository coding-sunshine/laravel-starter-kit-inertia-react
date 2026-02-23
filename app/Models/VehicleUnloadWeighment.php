<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class VehicleUnloadWeighment extends Model
{
    protected $fillable = [
        'vehicle_unload_id',
        'gross_weight_mt',
        'tare_weight_mt',
        'net_weight_mt',
        'weighment_type',
        'weighment_status',
        'data_source',
        'external_reference',
        'raw_payload',
        'weighment_time',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function unload()
    {
        return $this->belongsTo(VehicleUnload::class, 'vehicle_unload_id');
    }
}
