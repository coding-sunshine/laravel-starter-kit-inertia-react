<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class WordpressWebsite extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'wordpress_template_id',
        'title',
        'url',
        'type',
        'stage',
        'step',
        'is_custom_url',
        'is_verified_url',
        'instance_id',
        'url_key',
        'wp_username',
        'wp_password',
        'enquiry_recipient_emails',
        'primary_color',
        'secondary_color',
        'primary_text_color',
        'is_enabled',
        'legacy_id',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isPending(): bool
    {
        return $this->stage === 1;
    }

    public function isActive(): bool
    {
        return $this->stage === 3;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'embedding', 'api_token', 'wp_password']);
    }

    protected function casts(): array
    {
        return [
            'stage' => 'integer',
            'step' => 'integer',
            'is_custom_url' => 'boolean',
            'is_verified_url' => 'boolean',
            'is_enabled' => 'boolean',
            'enquiry_recipient_emails' => 'array',
        ];
    }
}
