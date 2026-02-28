<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

class ToolboxTalk extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'presenter_id',
        'topic',
        'content',
        'scheduled_date',
        'scheduled_time',
        'location',
        'attendee_driver_ids',
        'attendee_user_ids',
        'attendance_count',
        'status',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'attendee_driver_ids' => 'array',
        'attendee_user_ids' => 'array',
    ];

    public function presenter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'presenter_id');
    }
}
