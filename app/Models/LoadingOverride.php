<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoadingOverride extends Model
{
    protected $fillable = [
        'rake_id',
        'wagon_loading_id',
        'operator_id',
        'reason',
        'notes',
        'overload_mt',
        'estimated_penalty_at_time',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function wagonLoading(): BelongsTo
    {
        return $this->belongsTo(WagonLoading::class);
    }

    protected function casts(): array
    {
        return [
            'overload_mt' => 'decimal:3',
            'estimated_penalty_at_time' => 'decimal:2',
        ];
    }
}
