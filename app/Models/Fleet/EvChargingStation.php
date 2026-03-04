<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property string|null $external_id
 * @property string $name
 * @property string|null $operator
 * @property string|null $network
 * @property int|null $location_id
 * @property string|null $address
 * @property float|null $lat
 * @property float|null $lng
 * @property string $access_type
 * @property array|null $connector_types
 * @property array|null $charging_speeds
 * @property int $total_connectors
 * @property int $available_connectors
 * @property array|null $pricing_structure
 * @property array|null $operating_hours
 * @property array|null $amenities
 * @property string $status
 * @property \Carbon\Carbon|null $last_status_update
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Carbon\Carbon|null $deleted_at
 */
final class EvChargingStation extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'external_id',
        'name',
        'operator',
        'network',
        'location_id',
        'address',
        'lat',
        'lng',
        'access_type',
        'connector_types',
        'charging_speeds',
        'total_connectors',
        'available_connectors',
        'pricing_structure',
        'operating_hours',
        'amenities',
        'status',
        'last_status_update',
    ];

    protected $casts = [
        'connector_types' => 'array',
        'charging_speeds' => 'array',
        'pricing_structure' => 'array',
        'operating_hours' => 'array',
        'amenities' => 'array',
        'total_connectors' => 'integer',
        'available_connectors' => 'integer',
        'last_status_update' => 'datetime',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
    ];

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
