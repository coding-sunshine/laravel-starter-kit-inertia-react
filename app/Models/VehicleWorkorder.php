<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class VehicleWorkorder extends Model
{
    protected $table = 'vehicle_workorders';

    protected $guarded = [];

    public function siding()
    {
        return $this->belongsTo(Siding::class);
    }
}
