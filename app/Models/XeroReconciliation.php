<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $xero_invoice_id
 * @property string $xero_payment_id
 * @property float $amount
 * @property \Carbon\Carbon|null $payment_date
 * @property \Carbon\Carbon|null $reconciled_at
 * @property array|null $raw_payload
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class XeroReconciliation extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'xero_invoice_id',
        'xero_payment_id',
        'amount',
        'payment_date',
        'reconciled_at',
        'raw_payload',
    ];

    public function casts(): array
    {
        return [
            'amount' => 'float',
            'payment_date' => 'date',
            'reconciled_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function xeroInvoice(): BelongsTo
    {
        return $this->belongsTo(XeroInvoice::class);
    }
}
