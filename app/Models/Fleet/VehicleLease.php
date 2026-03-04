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
 * @property string|null $contract_id
 * @property string $lessor_name
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property float|null $monthly_payment
 * @property float|null $p11d_list_price
 * @property string $status
 */
final class VehicleLease extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'vehicle_leases';

    protected $fillable = [
        'vehicle_id',
        'contract_id',
        'lessor_name',
        'start_date',
        'end_date',
        'monthly_payment',
        'p11d_list_price',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_payment' => 'decimal:2',
        'p11d_list_price' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
