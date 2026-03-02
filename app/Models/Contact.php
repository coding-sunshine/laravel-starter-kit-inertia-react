<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Contact extends Model
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
        'first_name',
        'last_name',
        'job_title',
        'type',
        'stage',
        'source_id',
        'company_id',
        'company_name',
        'extra_attributes',
        'last_followup_at',
        'next_followup_at',
        'legacy_lead_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'extra_attributes' => 'array',
        'last_followup_at' => 'datetime',
        'next_followup_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(config('activitylog.sensitive_attributes', []));
    }

    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<ContactEmail, $this>
     */
    public function contactEmails(): HasMany
    {
        return $this->hasMany(ContactEmail::class);
    }

    /**
     * @return HasMany<ContactPhone, $this>
     */
    public function contactPhones(): HasMany
    {
        return $this->hasMany(ContactPhone::class);
    }

    // ─── Step 4: Reservations, Enquiries, Searches, Sales (inverse) ─────

    /** @return HasMany<PropertyReservation, $this> */
    public function propertyReservationsAsAgent(): HasMany
    {
        return $this->hasMany(PropertyReservation::class, 'agent_contact_id');
    }

    /** @return HasMany<PropertyReservation, $this> */
    public function propertyReservationsAsPrimary(): HasMany
    {
        return $this->hasMany(PropertyReservation::class, 'primary_contact_id');
    }

    /** @return HasMany<PropertyReservation, $this> */
    public function propertyReservationsAsSecondary(): HasMany
    {
        return $this->hasMany(PropertyReservation::class, 'secondary_contact_id');
    }

    /** @return HasMany<PropertyEnquiry, $this> */
    public function propertyEnquiriesAsClient(): HasMany
    {
        return $this->hasMany(PropertyEnquiry::class, 'client_contact_id');
    }

    /** @return HasMany<PropertyEnquiry, $this> */
    public function propertyEnquiriesAsAgent(): HasMany
    {
        return $this->hasMany(PropertyEnquiry::class, 'agent_contact_id');
    }

    /** @return HasMany<PropertySearch, $this> */
    public function propertySearchesAsClient(): HasMany
    {
        return $this->hasMany(PropertySearch::class, 'client_contact_id');
    }

    /** @return HasMany<PropertySearch, $this> */
    public function propertySearchesAsAgent(): HasMany
    {
        return $this->hasMany(PropertySearch::class, 'agent_contact_id');
    }

    /** @return HasMany<Sale, $this> */
    public function salesAsClient(): HasMany
    {
        return $this->hasMany(Sale::class, 'client_contact_id');
    }

    /** @return HasMany<Sale, $this> */
    public function salesAsAffiliateContact(): HasMany
    {
        return $this->hasMany(Sale::class, 'affiliate_contact_id');
    }

    /** @return HasMany<Sale, $this> */
    public function salesAsSubscriberContact(): HasMany
    {
        return $this->hasMany(Sale::class, 'subscriber_contact_id');
    }

    /** @return HasMany<Sale, $this> */
    public function salesAsSalesAgentContact(): HasMany
    {
        return $this->hasMany(Sale::class, 'sales_agent_contact_id');
    }

    /** @return HasMany<Sale, $this> */
    public function salesAsBdmContact(): HasMany
    {
        return $this->hasMany(Sale::class, 'bdm_contact_id');
    }

    /** @return HasMany<Sale, $this> */
    public function salesAsReferralPartnerContact(): HasMany
    {
        return $this->hasMany(Sale::class, 'referral_partner_contact_id');
    }

    /** @return HasMany<Sale, $this> */
    public function salesAsAgentContact(): HasMany
    {
        return $this->hasMany(Sale::class, 'agent_contact_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getFullNameAttribute(): string
    {
        return mb_trim($this->first_name.' '.$this->last_name) ?: 'Unknown';
    }
}
