<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;

final class BrochureMailJob extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'owner_contact_id',
        'mail_list_id',
        'status_id',
        'scheduled_at',
        'client_contact_ids',
        'name',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'client_contact_ids' => 'array',
    ];

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function ownerContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'owner_contact_id');
    }

    /**
     * @return BelongsTo<MailList, $this>
     */
    public function mailList(): BelongsTo
    {
        return $this->belongsTo(MailList::class);
    }

    /**
     * @return BelongsTo<MailJobStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(MailJobStatus::class);
    }
}

