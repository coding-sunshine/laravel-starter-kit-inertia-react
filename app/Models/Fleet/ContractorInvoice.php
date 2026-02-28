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
 * @property int $contractor_id
 * @property string $invoice_number
 * @property \Carbon\Carbon $invoice_date
 * @property \Carbon\Carbon|null $due_date
 * @property float|null $subtotal
 * @property float|null $tax_amount
 * @property float $total_amount
 * @property string $status
 * @property string|null $work_order_reference
 * @property string|null $description
 * @property \Carbon\Carbon|null $paid_date
 * @property string|null $payment_reference
 */
class ContractorInvoice extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'contractor_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'work_order_reference',
        'description',
        'paid_date',
        'payment_reference',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_date' => 'date',
    ];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }
}
