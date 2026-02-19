<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\StockLedgerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $siding_id
 * @property string $transaction_type receipt|dispatch|correction
 * @property int|null $vehicle_arrival_id
 * @property int|null $rake_id
 * @property decimal $quantity_mt
 * @property decimal $opening_balance_mt
 * @property decimal $closing_balance_mt
 * @property string|null $reference_number
 * @property string|null $remarks
 * @property int|null $created_by
 * @property int|null $verified_by
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
final class StockLedger extends Model
{
    /**
     * @use HasFactory<StockLedgerFactory>
     */
    use HasFactory, LogsActivity;

    protected $table = 'stock_ledgers';

    protected $fillable = [
        'siding_id',
        'transaction_type',
        'vehicle_arrival_id',
        'rake_id',
        'quantity_mt',
        'opening_balance_mt',
        'closing_balance_mt',
        'reference_number',
        'remarks',
        'created_by',
        'verified_by',
    ];

    protected $casts = [
        'quantity_mt' => 'decimal:2',
        'opening_balance_mt' => 'decimal:2',
        'closing_balance_mt' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logOnly([
                'transaction_type',
                'quantity_mt',
                'closing_balance_mt',
            ]);
    }

    /**
     * Siding relationship
     *
     * @return BelongsTo<Siding, $this>
     */
    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    /**
     * Vehicle arrival relationship (for receipts)
     *
     * @return BelongsTo<VehicleArrival, $this>
     */
    public function vehicleArrival(): BelongsTo
    {
        return $this->belongsTo(VehicleArrival::class);
    }

    /**
     * Rake relationship (for dispatches)
     *
     * @return BelongsTo<Rake, $this>
     */
    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    /**
     * Creator user
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Verifier user
     *
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope: by siding
     */
    public function scopeBySiding(Builder $query, int|Siding $siding): Builder
    {
        $sidingId = $siding instanceof Siding ? $siding->id : $siding;

        return $query->where('siding_id', $sidingId);
    }

    /**
     * Scope: receipts only
     */
    public function scopeReceipts(Builder $query): Builder
    {
        return $query->where('transaction_type', 'receipt');
    }

    /**
     * Scope: dispatches only
     */
    public function scopeDispatches(Builder $query): Builder
    {
        return $query->where('transaction_type', 'dispatch');
    }

    /**
     * Scope: corrections only
     */
    public function scopeCorrections(Builder $query): Builder
    {
        return $query->where('transaction_type', 'correction');
    }

    /**
     * Scope: recent (last 30 days)
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }

    /**
     * Check if this is a receipt transaction
     */
    public function isReceipt(): bool
    {
        return $this->transaction_type === 'receipt';
    }

    /**
     * Check if this is a dispatch transaction
     */
    public function isDispatch(): bool
    {
        return $this->transaction_type === 'dispatch';
    }

    /**
     * Check if this is a correction transaction
     */
    public function isCorrection(): bool
    {
        return $this->transaction_type === 'correction';
    }

    /**
     * Get the impact on balance (positive for receipts, negative for dispatches)
     */
    public function getBalanceImpact(): float|int
    {
        return match ($this->transaction_type) {
            'receipt' => $this->quantity_mt,
            'dispatch', 'correction' => -$this->quantity_mt,
            default => 0,
        };
    }
}
