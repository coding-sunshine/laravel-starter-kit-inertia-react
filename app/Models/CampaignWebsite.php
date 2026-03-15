<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $campaign_website_template_id
 * @property string $site_id
 * @property string $title
 * @property string|null $short_link
 * @property bool $is_multiple_property
 * @property bool $is_custom_font
 * @property string|null $font_link
 * @property string|null $font_family
 * @property string|null $primary_color
 * @property string|null $secondary_color
 * @property array|null $header
 * @property array|null $banner
 * @property array|null $page_content
 * @property array|null $footer
 * @property array|null $puck_content
 * @property bool $puck_enabled
 * @property int|null $created_by
 * @property int|null $legacy_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class CampaignWebsite extends Model implements HasMedia
{
    use BelongsToOrganization;
    use HasFactory;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'campaign_website_template_id',
        'site_id',
        'title',
        'short_link',
        'is_multiple_property',
        'is_custom_font',
        'font_link',
        'font_family',
        'primary_color',
        'secondary_color',
        'header',
        'banner',
        'page_content',
        'footer',
        'puck_content',
        'puck_enabled',
        'created_by',
        'legacy_id',
    ];

    /**
     * @return BelongsTo<CampaignWebsiteTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CampaignWebsiteTemplate::class, 'campaign_website_template_id');
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'campaign_website_project');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('banner')->singleFile();
        $this->addMediaCollection('assets');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'embedding', 'api_token']);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_multiple_property' => 'boolean',
            'is_custom_font' => 'boolean',
            'puck_enabled' => 'boolean',
            'header' => 'array',
            'banner' => 'array',
            'page_content' => 'array',
            'footer' => 'array',
            'puck_content' => 'array',
        ];
    }
}
