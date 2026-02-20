<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\VehicleArrivalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $siding_id
 * @property int $vehicle_id
 * @property int|null $indent_id
 * @property string $status pending|unloading|unloaded|completed|cancelled
 * @property CarbonInterface $arrived_at
 * @property CarbonInterface|null $unloading_started_at
 * @property CarbonInterface|null $unloading_completed_at
 * @property float|null $gross_weight
 * @property float|null $tare_weight
 * @property float|null $net_weight
 * @property float|null $unloaded_quantity
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
final class VehicleArrival extends Model
{
    /**
     * @use HasFactory<VehicleArrivalFactory>
     */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'siding_id',
        'vehicle_id',
        'indent_id',
        'status',
        'arrived_at',
        'shift',
        'unloading_started_at',
        'unloading_completed_at',
        'gross_weight',
        'tare_weight',
        'net_weight',
        'unloaded_quantity',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'arrived_at' => 'datetime',
        'unloading_started_at' => 'datetime',
        'unloading_completed_at' => 'datetime',
        'gross_weight' => 'decimal:2',
        'tare_weight' => 'decimal:2',
        'net_weight' => 'decimal:2',
        'unloaded_quantity' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logOnly([
                'status',
                'arrived_at',
                'unloading_started_at',
                'unloading_completed_at',
                'gross_weight',
                'unloaded_quantity',
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
     * Vehicle relationship
     *
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Indent relationship (optional - may not have an indent associated)
     *
     * @return BelongsTo<Indent, $this>
     */
    public function indent(): BelongsTo
    {
        return $this->belongsTo(Indent::class);
    }

    /**
     * Creator user relationship
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updater user relationship
     *
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Start unloading this vehicle
     */
    public function startUnloading(): void
    {
        $this->update([
            'status' => 'unloading',
            'unloading_started_at' => now(),
        ]);
    }

    /**
     * Complete unloading this vehicle
     */
    public function completeUnloading(float $unloadedQuantity): void
    {
        $this->update([
            'status' => 'unloaded',
            'unloading_completed_at' => now(),
            'unloaded_quantity' => $unloadedQuantity,
        ]);
    }

    /**
     * Cancel this arrival (e.g., vehicle left without unloading)
     */
    public function cancel(string $reason = ''): void
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason ?: $this->notes,
        ]);
    }

    /**
     * Mark as completed (final state)
     */
    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Calculate unloading duration in seconds
     */
    public function unloadingDurationSeconds(): ?int
    {
        if (! $this->unloading_started_at || ! $this->unloading_completed_at) {
            return null;
        }

        return (int) $this->unloading_completed_at->diffInSeconds($this->unloading_started_at);
    }

    /**
     * Check if currently unloading
     */
    public function isUnloading(): bool
    {
        return $this->status === 'unloading';
    }

    /**
     * Check if unloading is completed
     */
    public function isUnloaded(): bool
    {
        return $this->status === 'unloaded';
    }

    /**
     * Scope: pending arrivals
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: currently unloading
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function unloading(Builder $query): Builder
    {
        return $query->where('status', 'unloading');
    }

    /**
     * Scope: completed unloading
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function unloaded(Builder $query): Builder
    {
        return $query->where('status', 'unloaded');
    }

    /**
     * Scope: by siding
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function bySiding(Builder $query, int|Siding $siding): Builder
    {
        $sidingId = $siding instanceof Siding ? $siding->id : $siding;

        return $query->where('siding_id', $sidingId);
    }

    /**
     * Scope: recent arrivals (last 7 days)
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function recent(Builder $query): Builder
    {
        return $query->where('arrived_at', '>=', now()->subDays(7));
    }
}
