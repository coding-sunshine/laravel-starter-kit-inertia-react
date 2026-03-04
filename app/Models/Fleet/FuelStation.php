<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property string|null $external_id
 * @property string $name
 * @property string|null $brand
 * @property string $address
 * @property string|null $postcode
 * @property string|null $city
 * @property string $country
 * @property float|null $lat
 * @property float|null $lng
 * @property array|null $fuel_types_available
 * @property array|null $facilities
 * @property array|null $operating_hours
 * @property string|null $phone
 * @property string|null $website
 * @property float|null $price_quality_rating
 * @property bool $is_active
 */
final class FuelStation extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'organization_id',
        'external_id',
        'name',
        'brand',
        'address',
        'postcode',
        'city',
        'country',
        'lat',
        'lng',
        'fuel_types_available',
        'facilities',
        'operating_hours',
        'phone',
        'website',
        'price_quality_rating',
        'is_active',
    ];

    protected $casts = [
        'fuel_types_available' => 'array',
        'facilities' => 'array',
        'operating_hours' => 'array',
        'is_active' => 'boolean',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'price_quality_rating' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
