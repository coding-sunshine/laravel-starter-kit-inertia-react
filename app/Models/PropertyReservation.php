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

final class PropertyReservation extends Model
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
        'agent_contact_id',
        'primary_contact_id',
        'secondary_contact_id',
        'logged_in_user_id',
        'project_id',
        'lot_id',
        'purchase_price',
        'purchaser_type',
        'trustee_name',
        'abn_acn',
        'SMSF_trust_setup',
        'bare_trust_setup',
        'funds_rollover',
        'agree_lawlab',
        'firm',
        'broker',
        'finance_preapproval',
        'finance_days_req',
        'deposit',
        'land_deposit',
        'build_deposit',
        'contract_send',
        'agree',
        'agree_date',
        'family_trust',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'purchaser_type' => 'array',
        'SMSF_trust_setup' => 'array',
        'bare_trust_setup' => 'array',
        'funds_rollover' => 'array',
        'agree_lawlab' => 'array',
        'firm' => 'array',
        'broker' => 'array',
        'contract_send' => 'array',
        'family_trust' => 'array',
        'agree_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(config('activitylog.sensitive_attributes', []));
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function agentContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'agent_contact_id');
    }

    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    public function secondaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'secondary_contact_id');
    }

    public function loggedInUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_in_user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
