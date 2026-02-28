<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

class TachographCalibration extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'vehicle_id',
        'telematics_device_id',
        'calibration_date',
        'due_date',
        'certificate_reference',
        'status',
    ];

    protected $casts = [
        'calibration_date' => 'date',
        'due_date' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function telematicsDevice(): BelongsTo
    {
        return $this->belongsTo(TelematicsDevice::class);
    }
}
