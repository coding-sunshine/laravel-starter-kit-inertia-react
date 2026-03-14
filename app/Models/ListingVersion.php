<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class ListingVersion extends Model
{
    use LogsActivity;

    public $timestamps = false;

    protected $fillable = [
        'listable_type',
        'listable_id',
        'version',
        'snapshot',
        'change_summary',
        'created_by',
    ];

    public function listable(): MorphTo
    {
        return $this->morphTo();
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
            'snapshot' => 'array',
            'version' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
