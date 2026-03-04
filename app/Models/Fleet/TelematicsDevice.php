<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TelematicsDevice extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'organization_id',
        'vehicle_id',
        'device_id',
        'provider',
        'status',
        'installed_at',
        'last_sync_at',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
