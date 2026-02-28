<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $size
 * @property string|null $brand
 * @property string|null $pattern
 * @property string|null $category
 * @property int $quantity
 * @property int $min_quantity
 * @property float|null $unit_cost
 * @property string|null $storage_location
 * @property bool $is_active
 */
class TyreInventory extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'tyre_inventory';

    protected $fillable = [
        'size',
        'brand',
        'pattern',
        'category',
        'quantity',
        'min_quantity',
        'unit_cost',
        'storage_location',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'min_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
