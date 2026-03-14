<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $assigned_contact_id
 * @property int|null $attached_contact_id
 * @property int|null $assigned_to_user_id
 * @property int|null $project_id
 * @property string $title
 * @property string|null $description
 * @property \Carbon\Carbon|null $due_at
 * @property string $priority
 * @property string $type
 * @property string $status
 * @property bool $is_completed
 * @property \Carbon\Carbon|null $completed_at
 * @property bool $is_recurring
 * @property string|null $recurrence_frequency
 * @property int|null $legacy_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Task extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use Userstamps;

    public const string PRIORITY_LOW = 'low';

    public const string PRIORITY_MEDIUM = 'medium';

    public const string PRIORITY_HIGH = 'high';

    public const string PRIORITY_URGENT = 'urgent';

    public const string TYPE_CALL = 'call';

    public const string TYPE_EMAIL = 'email';

    public const string TYPE_MEETING = 'meeting';

    public const string TYPE_FOLLOW_UP = 'follow_up';

    public const string TYPE_OTHER = 'other';

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_IN_PROGRESS = 'in_progress';

    public const string STATUS_DONE = 'done';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'assigned_contact_id',
        'attached_contact_id',
        'assigned_to_user_id',
        'project_id',
        'title',
        'description',
        'due_at',
        'priority',
        'type',
        'status',
        'is_completed',
        'completed_at',
        'is_recurring',
        'recurrence_frequency',
        'legacy_id',
    ];

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function assignedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'assigned_contact_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function attachedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'attached_contact_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'embedding', 'api_token']);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_completed' => 'boolean',
            'is_recurring' => 'boolean',
        ];
    }
}
