<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RrPenaltySnapshot extends Model
{
    protected $fillable = [
        'rr_document_id',
        'rake_id',
        'penalty_code',
        'amount',
        'wagon_number',
        'wagon_sequence',
        'meta',
    ];

    public function rrDocument(): BelongsTo
    {
        return $this->belongsTo(RrDocument::class);
    }

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
        ];
    }
}
