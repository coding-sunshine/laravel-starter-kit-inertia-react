<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Alert extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'user_id',
        'siding_id',
        'rake_id',
        'type',
        'title',
        'body',
        'severity',
        'status',
        'resolved_at',
        'resolved_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forSiding(Builder $query, int $sidingId): Builder
    {
        return $query->where('siding_id', $sidingId);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forRake(Builder $query, int $rakeId): Builder
    {
        return $query->where('rake_id', $rakeId);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forSidings(Builder $query, array $sidingIds): Builder
    {
        return $query->whereIn('siding_id', $sidingIds);
    }
}
