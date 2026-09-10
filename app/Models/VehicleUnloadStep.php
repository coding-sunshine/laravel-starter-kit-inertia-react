<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class VehicleUnloadStep extends Model
{
    protected $fillable = [
        'vehicle_unload_id',
        'step_number',
        'status',
        'started_at',
        'completed_at',
        'remarks',
        'updated_by',
    ];

    public function unload()
    {
        return $this->belongsTo(VehicleUnload::class, 'vehicle_unload_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
