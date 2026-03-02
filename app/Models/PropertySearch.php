<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class PropertySearch extends Model
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
        'property_type',
        'no_of_bedrooms',
        'no_of_bathrooms',
        'no_of_carspaces',
        'property_config_other',
        'max_capacity',
        'build_status',
        'preferred_location',
        'preapproval',
        'lvr',
        'lender',
        'extra_instructions',
        'finance',
        'purchase_type',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'preapproval' => 'boolean',
        'purchaser_type' => 'array',
        'property_type' => 'array',
        'build_status' => 'array',
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
