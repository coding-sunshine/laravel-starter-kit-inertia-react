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

class VehicleCheck extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'vehicle_id',
        'vehicle_check_template_id',
        'performed_by_driver_id',
        'performed_by_user_id',
        'defect_id',
        'check_date',
        'status',
    ];

    protected $casts = [
        'check_date' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function vehicleCheckTemplate(): BelongsTo
    {
        return $this->belongsTo(VehicleCheckTemplate::class);
    }

    public function performedByDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'performed_by_driver_id');
    }

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    public function defect(): BelongsTo
    {
        return $this->belongsTo(Defect::class);
    }

    public function vehicleCheckItems(): HasMany
    {
        return $this->hasMany(VehicleCheckItem::class, 'vehicle_check_id');
    }
}
