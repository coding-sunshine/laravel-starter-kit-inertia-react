<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $organization_id
 * @property string $alert_type
 * @property bool $email_enabled
 * @property bool $sms_enabled
 * @property bool $push_enabled
 * @property bool $in_app_enabled
 * @property int $escalation_minutes
 * @property string|null $quiet_hours_start
 * @property string|null $quiet_hours_end
 * @property bool $weekend_enabled
 */
final class AlertPreference extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'alert_type',
        'email_enabled',
        'sms_enabled',
        'push_enabled',
        'in_app_enabled',
        'escalation_minutes',
        'quiet_hours_start',
        'quiet_hours_end',
        'weekend_enabled',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
        'escalation_minutes' => 'integer',
        'weekend_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
