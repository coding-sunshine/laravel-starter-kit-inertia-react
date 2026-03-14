<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class PushSchedule extends Model
{
    use LogsActivity;

    protected $fillable = [
        'pushable_type',
        'pushable_id',
        'organization_id',
        'channel',
        'scheduled_at',
        'published_at',
        'status',
        'notes',
        'created_by',
    ];

    public function pushable(): MorphTo
    {
        return $this->morphTo();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
