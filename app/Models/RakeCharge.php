<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RakeCharge extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'rake_id',
        'diverrt_destination_id',
        'charge_type',
        'amount',
        'data_source',
        'is_actual_charges',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_actual_charges' => 'boolean',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function diverrtDestination(): BelongsTo
    {
        return $this->belongsTo(DiverrtDestination::class, 'diverrt_destination_id');
    }

    public function rrCharges(): HasMany
    {
        return $this->hasMany(RrCharge::class);
    }

    public function rrPenaltySnapshots(): HasMany
    {
        return $this->hasMany(RrPenaltySnapshot::class);
    }

    public function appliedPenalties(): HasMany
    {
        return $this->hasMany(AppliedPenalty::class);
    }
}
