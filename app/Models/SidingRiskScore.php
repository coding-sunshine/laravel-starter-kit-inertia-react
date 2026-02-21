<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SidingRiskScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'siding_id',
        'score',
        'risk_factors',
        'trend',
        'calculated_at',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'risk_factors' => 'array',
            'calculated_at' => 'datetime',
        ];
    }
}
