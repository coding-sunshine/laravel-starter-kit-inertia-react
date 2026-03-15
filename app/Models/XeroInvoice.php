<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $sale_id
 * @property int $xero_connection_id
 * @property string $xero_invoice_id
 * @property string|null $invoice_number
 * @property float|null $amount
 * @property string $status
 * @property string $invoice_type
 * @property \Carbon\Carbon|null $issued_at
 * @property \Carbon\Carbon|null $due_at
 * @property \Carbon\Carbon|null $last_synced_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class XeroInvoice extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_id',
        'xero_connection_id',
        'xero_invoice_id',
        'invoice_number',
        'amount',
        'status',
        'invoice_type',
        'issued_at',
        'due_at',
        'last_synced_at',
    ];

    public function casts(): array
    {
        return [
            'amount' => 'float',
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function xeroConnection(): BelongsTo
    {
        return $this->belongsTo(XeroConnection::class);
    }

    public function xeroReconciliations(): HasMany
    {
        return $this->hasMany(XeroReconciliation::class);
    }
}
