<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SprRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $title
 * @property string|null $description
 * @property string|null $state
 * @property float $spr_price
 * @property string $payment_status
 * @property string|null $payment_transaction_id
 * @property string|null $payment_access_code
 * @property string $request_status
 * @property int|null $completed_by
 * @property \Carbon\Carbon|null $completed_at
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $legacy_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class SprRequest extends Model
{
    /** @use HasFactory<SprRequestFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'state',
        'spr_price',
        'payment_status',
        'payment_transaction_id',
        'payment_access_code',
        'request_status',
        'completed_by',
        'completed_at',
        'notes',
        'created_by',
        'legacy_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'spr_price' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }
}
