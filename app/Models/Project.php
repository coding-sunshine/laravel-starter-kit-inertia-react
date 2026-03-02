<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Project extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use LogsActivity;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'legacy_project_id',
        'title',
        'stage',
        'estate',
        'total_lots',
        'storeys',
        'min_landsize',
        'max_landsize',
        'min_living_area',
        'max_living_area',
        'bedrooms',
        'bathrooms',
        'min_bedrooms',
        'max_bedrooms',
        'min_bathrooms',
        'max_bathrooms',
        'garage',
        'min_rent',
        'max_rent',
        'avg_rent',
        'min_rent_yield',
        'max_rent_yield',
        'avg_rent_yield',
        'rent_to_sell_yield',
        'is_hot_property',
        'description',
        'min_price',
        'max_price',
        'avg_price',
        'body_corporate_fees',
        'min_body_corporate_fees',
        'max_body_corporate_fees',
        'rates_fees',
        'min_rates_fees',
        'max_rates_fees',
        'sub_agent_comms',
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
        'is_featured',
        'trust_details',
        'property_conditions',
        'is_co_living',
        'is_rooming',
        'is_rent_to_sell',
        'is_flexi',
        'is_exclusive',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_hot_property' => 'boolean',
        'is_archived' => 'boolean',
        'is_hidden' => 'boolean',
        'start_at' => 'date',
        'end_at' => 'date',
        'is_smsf' => 'boolean',
        'is_firb' => 'boolean',
        'is_ndis' => 'boolean',
        'is_cashflow_positive' => 'boolean',
        'is_featured' => 'boolean',
        'is_co_living' => 'boolean',
        'is_rooming' => 'boolean',
        'is_rent_to_sell' => 'boolean',
        'is_flexi' => 'boolean',
        'is_exclusive' => 'boolean',
        'trust_details' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(config('activitylog.sensitive_attributes', []));
    }

    /**
     * @return BelongsTo<Developer, $this>
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    /**
     * @return BelongsTo<Projecttype, $this>
     */
    public function projecttype(): BelongsTo
    {
        return $this->belongsTo(Projecttype::class);
    }

    /**
     * @return HasMany<Lot, $this>
     */
    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    /**
     * @return HasMany<ProjectUpdate, $this>
     */
    public function projectUpdates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class);
    }

    /**
     * @return HasMany<Flyer, $this>
     */
    public function flyers(): HasMany
    {
        return $this->hasMany(Flyer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
