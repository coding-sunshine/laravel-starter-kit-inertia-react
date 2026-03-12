<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RrWagonSnapshot extends Model
{
    protected $fillable = [
        'rr_document_id',
        'rake_id',
        'wagon_sequence',
        'wagon_number',
        'wagon_type',
        'pcc_weight_mt',
        'loaded_weight_mt',
        'permissible_weight_mt',
        'overload_weight_mt',
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
            'pcc_weight_mt' => 'decimal:2',
            'loaded_weight_mt' => 'decimal:2',
            'permissible_weight_mt' => 'decimal:2',
            'overload_weight_mt' => 'decimal:2',
            'meta' => 'array',
        ];
    }
}
