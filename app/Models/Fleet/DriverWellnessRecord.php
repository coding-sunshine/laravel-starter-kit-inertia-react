<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

class DriverWellnessRecord extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'driver_id',
        'record_date',
        'fatigue_level',
        'rest_hours',
        'sleep_quality',
        'mood',
        'notes',
    ];

    protected $casts = [
        'record_date' => 'date',
        'fatigue_level' => 'integer',
        'rest_hours' => 'decimal:2',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
