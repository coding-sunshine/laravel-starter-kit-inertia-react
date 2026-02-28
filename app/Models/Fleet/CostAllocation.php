<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $cost_center_id
 * @property \Carbon\Carbon $allocation_date
 * @property string $cost_type
 * @property string $source_type
 * @property int|null $source_id
 * @property float $amount
 * @property float $vat_amount
 * @property string|null $description
 * @property string|null $reference_number
 * @property string|null $invoice_number
 * @property string|null $supplier_name
 * @property int $allocated_by
 * @property string $approval_status
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 */
class CostAllocation extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'cost_center_id',
        'allocation_date',
        'cost_type',
        'source_type',
        'source_id',
        'amount',
        'vat_amount',
        'description',
        'reference_number',
        'invoice_number',
        'supplier_name',
        'allocated_by',
        'approval_status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'allocation_date' => 'date',
        'amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
