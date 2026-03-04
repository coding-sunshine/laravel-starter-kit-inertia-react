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
 * @property int $organization_id
 * @property int|null $garage_id
 * @property string $part_number
 * @property string|null $description
 * @property string|null $category
 * @property int $quantity
 * @property int $min_quantity
 * @property string $unit
 * @property float|null $unit_cost
 * @property float|null $reorder_cost
 * @property string|null $storage_location
 * @property int|null $supplier_id
 */
final class PartsInventory extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'parts_inventory';

    protected $fillable = [
        'garage_id',
        'part_number',
        'description',
        'category',
        'quantity',
        'min_quantity',
        'unit',
        'unit_cost',
        'reorder_cost',
        'storage_location',
        'supplier_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'min_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'reorder_cost' => 'decimal:2',
    ];

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(PartsSupplier::class, 'supplier_id');
    }
}
