<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;

final class FinanceAssessment extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'agent_contact_id',
        'logged_in_user_id',
        'data',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function agentContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'agent_contact_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function loggedInUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_in_user_id');
    }
}

