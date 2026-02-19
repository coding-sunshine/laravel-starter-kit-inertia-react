<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RrPrediction extends Model
{
    protected $fillable = [
        'rake_id',
        'predicted_weight_mt',
        'predicted_rr_date',
        'prediction_confidence',
        'prediction_status',
        'variance_percent',
    ];

    protected $casts = [
        'predicted_rr_date' => 'date',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }
}
