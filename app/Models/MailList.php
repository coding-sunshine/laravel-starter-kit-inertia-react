<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class MailList extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use LogsActivity;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'owner_contact_id',
        'name',
        'client_ids',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'client_ids' => 'array',
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
    public function ownerContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'owner_contact_id');
    }
}

