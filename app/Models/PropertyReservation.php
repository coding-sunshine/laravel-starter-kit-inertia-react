<?php

declare(strict_types=1);

namespace App\Models;

use App\States\PropertyReservation\ReservationState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property int|null $agent_contact_id
 * @property int|null $primary_contact_id
 * @property int|null $secondary_contact_id
 * @property int|null $logged_in_user_id
 * @property int|null $lot_id
 * @property int|null $project_id
 * @property string $stage
 * @property float|null $purchase_price
 * @property string $deposit_status
 * @property int|null $legacy_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class PropertyReservation extends Model
{
    use HasFactory;
    use HasStates;
    use LogsActivity;
    use SoftDeletes;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'agent_contact_id',
        'primary_contact_id',
        'secondary_contact_id',
        'logged_in_user_id',
        'lot_id',
        'project_id',
        'stage',
        'purchase_price',
        'purchaser_type',
        'trustee_name',
        'abn_acn',
        'smsf_trust_setup',
        'bare_trust_setup',
        'funds_rollover',
        'agree_lawlab',
        'firm',
        'broker',
        'finance_condition',
        'finance_days',
        'deposit',
        'deposit_bal',
        'build_deposit',
        'payment_duedate',
        'contract_send',
        'agree',
        'agree_date',
        'deposit_status',
        'eway_transaction_id',
        'eway_access_code',
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
    public function agentContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'agent_contact_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function secondaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'secondary_contact_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function loggedInUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_in_user_id');
    }

    /**
     * @return BelongsTo<Lot, $this>
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
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

    protected function registerStates(): void
    {
        $this->addState('stage', ReservationState::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchaser_type' => 'array',
            'smsf_trust_setup' => 'boolean',
            'bare_trust_setup' => 'boolean',
            'funds_rollover' => 'boolean',
            'agree_lawlab' => 'boolean',
            'finance_condition' => 'boolean',
            'agree' => 'boolean',
            'payment_duedate' => 'date',
            'contract_send' => 'date',
            'agree_date' => 'date',
        ];
    }
}
