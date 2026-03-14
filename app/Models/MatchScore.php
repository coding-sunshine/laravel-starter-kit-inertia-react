<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class MatchScore extends Model
{
    use LogsActivity;

    public $timestamps = false;

    protected $fillable = [
        'contact_id',
        'matchable_type',
        'matchable_id',
        'score',
        'factors',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function matchable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'embedding', 'api_token']);
    }

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'factors' => 'array',
            'computed_at' => 'datetime',
        ];
    }
}
