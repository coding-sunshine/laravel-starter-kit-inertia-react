<?php

declare(strict_types=1);

namespace App\Models;

use App\States\Sale\SaleState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\ModelStates\HasStates;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property int|null $legacy_id
 * @property int|null $client_contact_id
 * @property int|null $lot_id
 * @property int|null $project_id
 * @property string $status
 * @property \Carbon\Carbon|null $settled_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class Sale extends Model implements HasMedia
{
    use HasFactory;
    use HasStates;
    use InteractsWithMedia;
    use LogsActivity;
    use Searchable;
    use SoftDeletes;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'legacy_id',
        'client_contact_id',
        'lot_id',
        'project_id',
        'developer_id',
        'sales_agent_contact_id',
        'subscriber_contact_id',
        'bdm_contact_id',
        'referral_partner_contact_id',
        'affiliate_contact_id',
        'agent_contact_id',
        'status',
        'comm_in_notes',
        'comm_out_notes',
        'payment_terms',
        'expected_commissions',
        'finance_due_date',
        'comms_in_total',
        'comms_out_total',
        'piab_comm',
        'affiliate_comm',
        'subscriber_comm',
        'sales_agent_comm',
        'bdm_comm',
        'referral_partner_comm',
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
        'settled_at',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'status' => $this->status,
            'organization_id' => $this->organization_id,
            'created_at' => $this->created_at->timestamp,
        ];
    }

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
    public function salesAgentContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'sales_agent_contact_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function subscriberContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'subscriber_contact_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function bdmContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'bdm_contact_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function referralPartnerContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'referral_partner_contact_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function affiliateContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'affiliate_contact_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function agentContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'agent_contact_id');
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

    /**
     * @return BelongsTo<Developer, $this>
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    /**
     * @return HasMany<Commission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * @return HasMany<PaymentStage, $this>
     */
    public function paymentStages(): HasMany
    {
        return $this->hasMany(PaymentStage::class);
    }

    /**
     * @return MorphMany<PinnedNote, $this>
     */
    public function pinnedNotes(): MorphMany
    {
        return $this->morphMany(PinnedNote::class, 'noteable');
    }

    /**
     * @return HasMany<DealDocument, $this>
     */
    public function dealDocuments(): HasMany
    {
        return $this->hasMany(DealDocument::class, 'deal_id')->where('deal_type', 'sale');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
        $this->addMediaCollection('contracts');
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
        $this->addState('status', SaleState::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected_commissions' => 'array',
            'divide_percent' => 'array',
            'is_comments_enabled' => 'boolean',
            'is_sas_enabled' => 'boolean',
            'is_sas_max' => 'boolean',
            'finance_due_date' => 'date',
            'status_updated_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }
}
