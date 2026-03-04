<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property int|null $tyre_inventory_id
 * @property string $position
 * @property string|null $size
 * @property string|null $brand
 * @property \Carbon\Carbon|null $fitted_at
 * @property float|null $tread_depth_mm
 * @property int|null $odometer_at_fit
 * @property string|null $notes
 */
final class VehicleTyre extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'vehicle_id',
        'tyre_inventory_id',
        'position',
        'size',
        'brand',
        'fitted_at',
        'tread_depth_mm',
        'odometer_at_fit',
        'notes',
    ];

    protected $casts = [
        'fitted_at' => 'date',
        'tread_depth_mm' => 'decimal:2',
        'odometer_at_fit' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function tyreInventory(): BelongsTo
    {
        return $this->belongsTo(TyreInventory::class, 'tyre_inventory_id');
    }
}
