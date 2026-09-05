<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Vehicle extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'vehicle_number',
        'rfid_tag',
        'permitted_capacity_mt',
        'tare_weight_mt',
        'owner_name',
        'vehicle_type',
        'gps_device_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function vehicleUnloads(): HasMany
    {
        return $this->hasMany(VehicleUnload::class);
    }
}
