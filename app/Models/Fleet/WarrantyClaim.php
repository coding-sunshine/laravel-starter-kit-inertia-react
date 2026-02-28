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
 * @property int $work_order_id
 * @property string $claim_number
 * @property string $status
 * @property float|null $claim_amount
 * @property float|null $settlement_amount
 * @property \Carbon\Carbon|null $submitted_date
 * @property \Carbon\Carbon|null $settled_at
 */
class WarrantyClaim extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'work_order_id',
        'claim_number',
        'status',
        'claim_amount',
        'settlement_amount',
        'submitted_date',
        'settled_at',
    ];

    protected $casts = [
        'claim_amount' => 'decimal:2',
        'settlement_amount' => 'decimal:2',
        'submitted_date' => 'date',
        'settled_at' => 'date',
    ];

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
