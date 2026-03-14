<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property int|null $client_contact_id
 * @property int|null $agent_contact_id
 * @property int|null $logged_in_user_id
 * @property int|null $legacy_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class PropertySearch extends Model
{
    use HasFactory;
    use LogsActivity;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'client_contact_id',
        'agent_contact_id',
        'logged_in_user_id',
        'preferred_states',
        'preferred_project_types',
        'budget_min',
        'budget_max',
        'bedrooms_min',
        'bedrooms_max',
        'notes',
        'legacy_id',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function clientContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'client_contact_id');
    }

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
            'preferred_states' => 'array',
            'preferred_project_types' => 'array',
        ];
    }
}
