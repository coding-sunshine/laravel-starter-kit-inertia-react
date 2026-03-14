<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property int|null $legacy_id
 * @property string|null $slug
 * @property string $title
 * @property string $stage
 * @property string|null $estate
 * @property int|null $total_lots
 * @property int|null $storeys
 * @property float|null $min_landsize
 * @property float|null $max_landsize
 * @property float|null $living_area
 * @property int|null $bedrooms
 * @property int|null $bathrooms
 * @property int|null $garage
 * @property float|null $min_rent
 * @property float|null $max_rent
 * @property float|null $avg_rent
 * @property float|null $rent_yield
 * @property bool $is_hot_property
 * @property string|null $description
 * @property string|null $description_summary
 * @property float|null $min_price
 * @property float|null $max_price
 * @property float|null $avg_price
 * @property float|null $body_corporate_fees
 * @property float|null $rates_fees
 * @property bool $is_archived
 * @property bool $is_hidden
 * @property \Carbon\Carbon|null $start_at
 * @property \Carbon\Carbon|null $end_at
 * @property bool $is_smsf
 * @property bool $is_firb
 * @property bool $is_ndis
 * @property bool $is_cashflow_positive
 * @property string|null $build_time
 * @property float|null $historical_growth
 * @property string|null $land_info
 * @property int|null $developer_id
 * @property int|null $projecttype_id
 * @property float|null $lat
 * @property float|null $lng
 * @property bool $is_featured
 * @property int|null $featured_order
 * @property bool $is_co_living
 * @property bool $is_high_cap_growth
 * @property bool $is_rooming
 * @property bool $is_rent_to_sell
 * @property bool $is_exclusive
 * @property string|null $suburb
 * @property string|null $state
 * @property string|null $postcode
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use BelongsToOrganization;

    use HasFactory;
    use HasSlug;
    use LogsActivity;
    use Searchable;
    use SoftDeletes;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'legacy_id',
        'slug',
        'title',
        'stage',
        'estate',
        'total_lots',
        'storeys',
        'min_landsize',
        'max_landsize',
        'living_area',
        'bedrooms',
        'bathrooms',
        'garage',
        'min_rent',
        'max_rent',
        'avg_rent',
        'rent_yield',
        'is_hot_property',
        'description',
        'description_summary',
        'min_price',
        'max_price',
        'avg_price',
        'body_corporate_fees',
        'rates_fees',
        'is_archived',
        'is_hidden',
        'start_at',
        'end_at',
        'is_smsf',
        'is_firb',
        'is_ndis',
        'is_cashflow_positive',
        'build_time',
        'historical_growth',
        'land_info',
        'developer_id',
        'projecttype_id',
        'lat',
        'lng',
        'is_featured',
        'featured_order',
        'is_co_living',
        'is_high_cap_growth',
        'is_rooming',
        'is_rent_to_sell',
        'is_exclusive',
        'suburb',
        'state',
        'postcode',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    public function projecttype(): BelongsTo
    {
        return $this->belongsTo(Projecttype::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    public function projectUpdates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class);
    }

    public function flyers(): HasMany
    {
        return $this->hasMany(Flyer::class);
    }

    public function favouritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_project_favourites')
            ->withTimestamps();
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'suburb' => $this->suburb ?? '',
            'state' => $this->state ?? '',
            'developer' => $this->developer?->name ?? '',
            'stage' => $this->stage ?? '',
            'description_summary' => $this->description_summary ?? '',
            'min_price' => (float) ($this->min_price ?? 0),
            'max_price' => (float) ($this->max_price ?? 0),
            'is_featured' => (bool) $this->is_featured,
            'is_hot_property' => (bool) $this->is_hot_property,
            'is_archived' => (bool) $this->is_archived,
            'organization_id' => $this->organization_id,
            'location' => ($this->lat && $this->lng)
                ? ['lat' => (float) $this->lat, 'lng' => (float) $this->lng]
                : null,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['description']);
    }

    protected function casts(): array
    {
        return [
            'is_hot_property' => 'boolean',
            'is_archived' => 'boolean',
            'is_hidden' => 'boolean',
            'is_smsf' => 'boolean',
            'is_firb' => 'boolean',
            'is_ndis' => 'boolean',
            'is_cashflow_positive' => 'boolean',
            'is_featured' => 'boolean',
            'is_co_living' => 'boolean',
            'is_high_cap_growth' => 'boolean',
            'is_rooming' => 'boolean',
            'is_rent_to_sell' => 'boolean',
            'is_exclusive' => 'boolean',
            'start_at' => 'date',
            'end_at' => 'date',
            'lat' => 'decimal:8',
            'lng' => 'decimal:8',
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'avg_price' => 'decimal:2',
            'min_rent' => 'decimal:2',
            'max_rent' => 'decimal:2',
            'avg_rent' => 'decimal:2',
        ];
    }
}
