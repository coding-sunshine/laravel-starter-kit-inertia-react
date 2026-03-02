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

final class Task extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'assigned_contact_id',
        'attached_contact_id',
        'assigned_to_user_id',
        'title',
        'description',
        'due_at',
        'priority',
        'status',
        'completed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(config('activitylog.sensitive_attributes', []));
    }

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
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}

