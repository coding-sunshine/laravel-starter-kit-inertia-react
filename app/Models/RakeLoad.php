<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RakeLoad extends Model
{
    protected $fillable = [
        'rake_id',
        'placement_time',
        'free_time_minutes',
        'status',
    ];

    protected $casts = [
        'placement_time' => 'datetime',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function wagonLoadings(): HasMany
    {
        return $this->hasMany(RakeWagonLoading::class);
    }

    public function weighments(): HasMany
    {
        return $this->hasMany(Weighment::class);
    }

    public function elapsedMinutes(): int
    {
        return now()->diffInMinutes($this->placement_time);
    }

    public function guardInspections()
    {
        return $this->hasMany(GuardInspection::class);
    }
}
