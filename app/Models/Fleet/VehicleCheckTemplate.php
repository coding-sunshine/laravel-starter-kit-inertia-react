<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

class VehicleCheckTemplate extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'name',
        'code',
        'check_type',
        'category',
        'checklist',
        'workflow_route',
        'completion_percentage_threshold',
        'is_active',
    ];

    protected $casts = [
        'checklist' => 'array',
        'is_active' => 'boolean',
    ];

    public function vehicleChecks(): HasMany
    {
        return $this->hasMany(VehicleCheck::class, 'vehicle_check_template_id');
    }
}
