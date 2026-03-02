<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Sale extends Model
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
        'client_contact_id',
        'lot_id',
        'project_id',
        'developer_id',
        'comm_in_notes',
        'comm_out_notes',
        'payment_terms',
        'expected_commissions',
        'finance_due_date',
        'comms_in_total',
        'comms_out_total',
        'piab_comm',
        'affiliate_contact_id',
        'affiliate_comm',
        'subscriber_contact_id',
        'subscriber_comm',
        'sales_agent_contact_id',
        'sales_agent_comm',
        'bdm_contact_id',
        'bdm_comm',
        'referral_partner_contact_id',
        'referral_partner_comm',
        'agent_contact_id',
        'agent_comm',
        'divide_percent',
        'is_comments_enabled',
        'comments',
        'is_sas_enabled',
        'is_sas_max',
        'sas_percent',
        'sas_fee',
        'summary_note',
        'status_updated_at',
        'custom_attributes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_comments_enabled' => 'boolean',
        'is_sas_enabled' => 'boolean',
        'is_sas_max' => 'boolean',
        'expected_commissions' => 'array',
        'divide_percent' => 'array',
        'custom_attributes' => 'array',
        'finance_due_date' => 'date',
        'status_updated_at' => 'datetime',
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

    public function clientContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'client_contact_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    public function affiliateContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'affiliate_contact_id');
    }

    public function subscriberContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'subscriber_contact_id');
    }

    public function salesAgentContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'sales_agent_contact_id');
    }

    public function bdmContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'bdm_contact_id');
    }

    public function referralPartnerContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'referral_partner_contact_id');
    }

    public function agentContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'agent_contact_id');
    }

    /**
     * @return MorphMany<Commission, $this>
     */
    public function commissions(): MorphMany
    {
        return $this->morphMany(Commission::class, 'commissionable');
    }
}
