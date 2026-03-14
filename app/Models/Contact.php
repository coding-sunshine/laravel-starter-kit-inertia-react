<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property string $contact_origin
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $job_title
 * @property string $type
 * @property string|null $stage
 * @property int|null $source_id
 * @property int|null $company_id
 * @property string|null $company_name
 * @property array|null $extra_attributes
 * @property \Carbon\Carbon|null $last_followup_at
 * @property \Carbon\Carbon|null $next_followup_at
 * @property \Carbon\Carbon|null $last_contacted_at
 * @property int|null $lead_score
 * @property int|null $legacy_lead_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Contact extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use LogsActivity;
    use Searchable;
    use SoftDeletes;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'contact_origin',
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
        'last_contacted_at',
        'lead_score',
        'legacy_lead_id',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(ContactEmail::class);
    }

    public function phones(): HasMany
    {
        return $this->hasMany(ContactPhone::class);
    }

    public function primaryEmail(): HasMany
    {
        return $this->hasMany(ContactEmail::class)->where('is_primary', true);
    }

    public function primaryPhone(): HasMany
    {
        return $this->hasMany(ContactPhone::class)->where('is_primary', true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name ?? '',
            'full_name' => mb_trim($this->first_name.' '.($this->last_name ?? '')),
            'type' => $this->type,
            'stage' => $this->stage ?? '',
            'contact_origin' => $this->contact_origin,
            'company_name' => $this->company_name ?? '',
            'job_title' => $this->job_title ?? '',
            'lead_score' => $this->lead_score,
            'organization_id' => $this->organization_id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll();
    }

    protected function casts(): array
    {
        return [
            'extra_attributes' => 'array',
            'last_followup_at' => 'datetime',
            'next_followup_at' => 'datetime',
            'last_contacted_at' => 'datetime',
        ];
    }
}
