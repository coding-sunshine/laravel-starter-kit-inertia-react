<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AppliedPenalty extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'penalty_type_id',
        'rake_id',
        'wagon_id',
        'quantity',
        'distance',
        'rate',
        'amount',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'distance' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function penaltyType(): BelongsTo
    {
        return $this->belongsTo(PenaltyType::class);
    }

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function wagon(): BelongsTo
    {
        return $this->belongsTo(Wagon::class);
    }
}
