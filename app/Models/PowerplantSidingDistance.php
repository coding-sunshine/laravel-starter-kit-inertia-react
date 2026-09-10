<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PowerplantSidingDistance extends Model
{
    protected $table = 'powerplant_siding_distances';

    protected $fillable = [
        'power_plant_id',
        'siding_id',
        'distance_km',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
    ];

    public function powerPlant()
    {
        return $this->belongsTo(PowerPlant::class);
    }

    public function siding()
    {
        return $this->belongsTo(Siding::class);
    }
}
