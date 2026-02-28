<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $work_order_id
 * @property int|null $parts_inventory_id
 * @property string $line_type
 * @property string|null $description
 * @property float $quantity
 * @property float|null $unit_price
 * @property float|null $total
 * @property int $sort_order
 */
class WorkOrderLine extends Model
{
    protected $table = 'work_order_lines';

    protected $fillable = [
        'work_order_id',
        'parts_inventory_id',
        'line_type',
        'description',
        'quantity',
        'unit_price',
        'total',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * @return BelongsTo<PartsInventory, $this>
     */
    public function partsInventory(): BelongsTo
    {
        return $this->belongsTo(PartsInventory::class, 'parts_inventory_id');
    }
}
