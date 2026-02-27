<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property string|null $registration
 * @property string|null $fleet_number
 * @property string $type
 * @property string|null $make
 * @property string|null $model
 * @property int|null $year
 * @property int|null $home_location_id
 * @property int|null $weight_kg
 * @property int|null $max_payload_kg
 * @property string $status
 * @property string $compliance_status
 * @property \Carbon\Carbon|null $inspection_expiry_date
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Carbon\Carbon|null $deleted_at
 */
class Trailer extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'registration',
        'fleet_number',
        'type',
        'make',
        'model',
        'year',
        'home_location_id',
        'weight_kg',
        'max_payload_kg',
        'status',
        'compliance_status',
        'inspection_expiry_date',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'weight_kg' => 'integer',
        'max_payload_kg' => 'integer',
        'inspection_expiry_date' => 'date',
    ];

    /**
     * @return BelongsTo<Location, $this>
     */
    public function homeLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'home_location_id');
    }
}
