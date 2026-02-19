<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class VehicleUnload extends Model
{
    use SoftDeletes, Userstamps;

    protected $table = 'vehicle_unload';

    protected $fillable = [
        'siding_id',
        'vehicle_id',
        'jimms_challan_number',
        'arrival_time',
        'shift',
        'unload_start_time',
        'unload_end_time',
        'mine_weight_mt',
        'weighment_weight_mt',
        'variance_mt',
        'state',
        'remarks',
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
        'unload_start_time' => 'datetime',
        'unload_end_time' => 'datetime',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
