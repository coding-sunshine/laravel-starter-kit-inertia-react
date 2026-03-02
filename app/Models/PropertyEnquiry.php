<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class PropertyEnquiry extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use SoftDeletes;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'client_contact_id',
        'agent_contact_id',
        'logged_in_user_id',
        'purchaser_type',
        'max_capacity',
        'preferred_location',
        'preapproval',
        'property',
        'requesting_info',
        'instructions',
        'inspection_person',
        'inspection_date',
        'inspection_time',
        'cash_purchase',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'preapproval' => 'boolean',
        'cash_purchase' => 'boolean',
        'purchaser_type' => 'array',
        'property' => 'array',
        'requesting_info' => 'array',
        'inspection_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function clientContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'client_contact_id');
    }

    public function agentContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'agent_contact_id');
    }

    public function loggedInUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_in_user_id');
    }
}
