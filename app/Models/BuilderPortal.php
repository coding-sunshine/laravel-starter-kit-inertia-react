<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class BuilderPortal extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'logo_path',
        'primary_color',
        'secondary_color',
        'contact_email',
        'contact_phone',
        'disclaimer',
        'show_prices',
        'show_agent_details',
        'is_active',
        'allowed_project_ids',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
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
            'show_prices' => 'boolean',
            'show_agent_details' => 'boolean',
            'is_active' => 'boolean',
            'allowed_project_ids' => 'array',
        ];
    }
}
