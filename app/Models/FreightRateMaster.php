<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FreightRateMaster extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;

    protected $table = 'freight_rate_master';

    protected $fillable = [
        'commodity_code',
        'commodity_name',
        'class_code',
        'risk_rate',
        'distance_from_km',
        'distance_to_km',
        'rate_per_mt',
        'gst_percent',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
