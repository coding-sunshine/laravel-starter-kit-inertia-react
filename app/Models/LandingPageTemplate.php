<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Wildside\Userstamps\Userstamps;

final class LandingPageTemplate extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'organization_id',
        'campaign_website_id',
        'name',
        'slug',
        'description',
        'headline',
        'sub_headline',
        'html_content',
        'puck_content',
        'puck_enabled',
        'status',
        'meta_title',
        'meta_description',
        'seo_config',
        'is_active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function campaignWebsite(): BelongsTo
    {
        return $this->belongsTo(CampaignWebsite::class);
    }

    protected function casts(): array
    {
        return [
            'puck_content' => 'array',
            'puck_enabled' => 'boolean',
            'seo_config' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
