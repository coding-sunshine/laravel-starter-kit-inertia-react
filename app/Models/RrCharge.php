<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RrCharge extends Model
{
    protected $fillable = [
        'rr_document_id',
        'charge_code',
        'charge_name',
        'amount',
        'meta',
    ];

    public function rrDocument(): BelongsTo
    {
        return $this->belongsTo(RrDocument::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
        ];
    }
}
