<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PenaltyPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'siding_id',
        'prediction_date',
        'risk_level',
        'predicted_types',
        'predicted_amount_min',
        'predicted_amount_max',
        'factors',
        'recommendations',
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
            'prediction_date' => 'date',
            'predicted_types' => 'array',
            'predicted_amount_min' => 'decimal:2',
            'predicted_amount_max' => 'decimal:2',
            'factors' => 'array',
            'recommendations' => 'array',
        ];
    }
}
