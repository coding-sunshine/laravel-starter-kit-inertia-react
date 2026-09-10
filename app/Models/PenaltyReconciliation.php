<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;

final class PenaltyReconciliation extends Model
{
    use HasFactory;
    use Userstamps;

    protected $fillable = [
        'rake_id',
        'penalty_code',
        'predicted_amount',
        'billed_amount',
        'variance',
        'variance_pct',
        'dispute_candidate',
        'notes',
        'reconciled_at',
    ];

    protected $casts = [
        'predicted_amount' => 'decimal:2',
        'billed_amount' => 'decimal:2',
        'variance' => 'decimal:2',
        'variance_pct' => 'decimal:2',
        'dispute_candidate' => 'boolean',
        'notes' => 'array',
        'reconciled_at' => 'datetime',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }
}
