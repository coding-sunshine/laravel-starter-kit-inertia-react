<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int|null $parent_cost_center_id
 * @property string $cost_center_type
 * @property int|null $manager_user_id
 * @property float|null $budget_annual
 * @property float|null $budget_monthly
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Carbon\Carbon|null $deleted_at
 */
class CostCenter extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'code',
        'name',
        'description',
        'parent_cost_center_id',
        'cost_center_type',
        'manager_user_id',
        'budget_annual',
        'budget_monthly',
        'is_active',
    ];

    protected $casts = [
        'budget_annual' => 'decimal:2',
        'budget_monthly' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<CostCenter, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'parent_cost_center_id');
    }

    /**
     * @return HasMany<CostCenter, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(CostCenter::class, 'parent_cost_center_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }
}
