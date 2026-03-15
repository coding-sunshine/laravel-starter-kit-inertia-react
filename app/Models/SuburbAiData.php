<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property int|null $suburb_id
 * @property string $suburb_name
 * @property string|null $state
 * @property string|null $postcode
 * @property string $source
 * @property float|null $median_house_price
 * @property float|null $median_unit_price
 * @property float|null $median_rent_house
 * @property float|null $median_rent_unit
 * @property float|null $rental_yield
 * @property float|null $annual_growth
 * @property array|null $price_rent_json
 * @property array|null $ai_insights
 * @property \Carbon\Carbon|null $fetched_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class SuburbAiData extends Model
{
    use BelongsToOrganization;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'suburb_id',
        'suburb_name',
        'state',
        'postcode',
        'source',
        'median_house_price',
        'median_unit_price',
        'median_rent_house',
        'median_rent_unit',
        'rental_yield',
        'annual_growth',
        'price_rent_json',
        'ai_insights',
        'fetched_at',
    ];

    public function suburb(): BelongsTo
    {
        return $this->belongsTo(Suburb::class);
    }

    protected function casts(): array
    {
        return [
            'median_house_price' => 'decimal:2',
            'median_unit_price' => 'decimal:2',
            'median_rent_house' => 'decimal:2',
            'median_rent_unit' => 'decimal:2',
            'rental_yield' => 'decimal:2',
            'annual_growth' => 'decimal:2',
            'price_rent_json' => 'array',
            'ai_insights' => 'array',
            'fetched_at' => 'datetime',
        ];
    }
}
