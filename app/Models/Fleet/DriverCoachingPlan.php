<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class DriverCoachingPlan extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'driver_id',
        'plan_type',
        'title',
        'objectives',
        'objectives_json',
        'status',
        'due_date',
        'completed_at',
        'assigned_coach_id',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'date',
        'objectives_json' => 'array',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function assignedCoach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_coach_id');
    }
}
