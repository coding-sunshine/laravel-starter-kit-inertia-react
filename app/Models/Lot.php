<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Lot extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'legacy_lot_id',
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
        'storyes',
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
        'rent_to_sell_yield',
        'rates',
        'five_percent_share_price',
        'sub_agent_comms',
        'body_corporation',
        'is_archived',
        'is_nras',
        'is_smsf',
        'is_cashflow_positive',
        'completion',
        'uuid',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_archived' => 'boolean',
        'is_nras' => 'boolean',
        'is_smsf' => 'boolean',
        'is_cashflow_positive' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(config('activitylog.sensitive_attributes', []));
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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
