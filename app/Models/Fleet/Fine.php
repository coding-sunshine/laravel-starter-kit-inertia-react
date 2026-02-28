<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $vehicle_id
 * @property int|null $driver_id
 * @property string $fine_type
 * @property string|null $offence_description
 * @property \Carbon\Carbon $offence_date
 * @property float $amount
 * @property float $amount_paid
 * @property \Carbon\Carbon|null $due_date
 * @property \Carbon\Carbon|null $appeal_deadline
 * @property string $status
 * @property string|null $appeal_notes
 * @property string|null $external_reference
 * @property string|null $issuing_authority
 */
class Fine extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'fine_type',
        'offence_description',
        'offence_date',
        'amount',
        'amount_paid',
        'due_date',
        'appeal_deadline',
        'status',
        'appeal_notes',
        'external_reference',
        'issuing_authority',
    ];

    protected $casts = [
        'offence_date' => 'date',
        'due_date' => 'date',
        'appeal_deadline' => 'date',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
