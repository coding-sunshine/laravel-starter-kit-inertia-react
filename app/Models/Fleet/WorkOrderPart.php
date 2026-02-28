<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $work_order_id
 * @property int $parts_inventory_id
 * @property float $quantity_used
 * @property float|null $unit_cost
 * @property float|null $total_cost
 * @property string|null $notes
 */
class WorkOrderPart extends Model
{
    protected $table = 'work_order_parts';

    protected $fillable = [
        'work_order_id',
        'parts_inventory_id',
        'quantity_used',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
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
