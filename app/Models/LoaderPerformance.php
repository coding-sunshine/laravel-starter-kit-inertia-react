<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoaderPerformance extends Model
{
    protected $fillable = [
        'loader_id',
        'as_of_date',
        'rakes_processed',
        'average_loading_time_minutes',
        'consistency_variance_minutes',
        'overload_incidents',
        'quality_score',
    ];

    protected $casts = [
        'as_of_date' => 'date',
    ];

    public function loader(): BelongsTo
    {
        return $this->belongsTo(Loader::class);
    }
}
