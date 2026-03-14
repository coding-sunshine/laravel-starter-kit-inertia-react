<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property int $project_id
 * @property int|null $legacy_id
 * @property string|null $slug
 * @property string|null $title
 * @property float|null $land_price
 * @property float|null $build_price
 * @property string|null $stage
 * @property string|null $level
 * @property string|null $building
 * @property string|null $floorplan
 * @property int|null $car
 * @property string|null $storage
 * @property string|null $view
 * @property int|null $garage
 * @property string|null $aspect
 * @property float|null $internal
 * @property float|null $external
 * @property float|null $total
 * @property int|null $storeys
 * @property float|null $land_size
 * @property string $title_status
 * @property float|null $living_area
 * @property float|null $price
 * @property int|null $bedrooms
 * @property int|null $bathrooms
 * @property int|null $study
 * @property bool $mpr
 * @property bool $powder_room
 * @property float|null $balcony
 * @property float|null $rent_yield
 * @property float|null $weekly_rent
 * @property float|null $rates
 * @property float|null $body_corporation
 * @property bool $is_archived
 * @property bool $is_nras
 * @property bool $is_smsf
 * @property bool $is_cashflow_positive
 * @property \Carbon\Carbon|null $completion
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Lot extends Model
{
    /** @use HasFactory<LotFactory> */
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
        'project_id',
        'legacy_id',
        'slug',
        'title',
        'land_price',
        'build_price',
        'stage',
        'level',
        'building',
        'floorplan',
        'car',
        'storage',
        'view',
        'garage',
        'aspect',
        'internal',
        'external',
        'total',
        'storeys',
        'land_size',
        'title_status',
        'living_area',
        'price',
        'bedrooms',
        'bathrooms',
        'study',
        'mpr',
        'powder_room',
        'balcony',
        'rent_yield',
        'weekly_rent',
        'rates',
        'body_corporation',
        'is_archived',
        'is_nras',
        'is_smsf',
        'is_cashflow_positive',
        'completion',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['project_id', 'title'])
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function flyers(): HasMany
    {
        return $this->hasMany(Flyer::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title ?? "Lot {$this->id}",
            'project_id' => $this->project_id,
            'project_title' => $this->project?->title ?? '',
            'bedrooms' => (int) ($this->bedrooms ?? 0),
            'bathrooms' => (int) ($this->bathrooms ?? 0),
            'car' => (int) ($this->car ?? 0),
            'price' => (float) ($this->price ?? 0),
            'title_status' => $this->title_status ?? 'available',
            'is_smsf' => (bool) $this->is_smsf,
            'is_archived' => (bool) $this->is_archived,
            'organization_id' => $this->project?->organization_id,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept([]);
    }

    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
            'is_nras' => 'boolean',
            'is_smsf' => 'boolean',
            'is_cashflow_positive' => 'boolean',
            'mpr' => 'boolean',
            'powder_room' => 'boolean',
            'completion' => 'date',
            'price' => 'decimal:2',
            'land_price' => 'decimal:2',
            'build_price' => 'decimal:2',
            'weekly_rent' => 'decimal:2',
        ];
    }
}
