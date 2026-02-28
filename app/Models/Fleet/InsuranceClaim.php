<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $incident_id
 * @property int $insurance_policy_id
 * @property string $claim_number
 * @property string $claim_type
 * @property string $status
 */
class InsuranceClaim extends Model implements HasMedia
{
    use BelongsToOrganization;
    use InteractsWithMedia;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'incident_id', 'insurance_policy_id', 'claim_number', 'claim_type',
        'claim_amount', 'excess_amount', 'settlement_amount', 'status',
        'submitted_date', 'acknowledged_date', 'settlement_date',
        'claim_handler_name', 'claim_handler_contact', 'assessor_name', 'assessor_report',
        'supporting_documents', 'correspondence_log', 'recovery_amount',
        'legal_action_required', 'legal_representative',
    ];

    protected $casts = [
        'claim_amount' => 'decimal:2',
        'excess_amount' => 'decimal:2',
        'settlement_amount' => 'decimal:2',
        'recovery_amount' => 'decimal:2',
        'submitted_date' => 'date',
        'acknowledged_date' => 'date',
        'settlement_date' => 'date',
        'assessor_report' => 'array',
        'supporting_documents' => 'array',
        'correspondence_log' => 'array',
        'legal_action_required' => 'boolean',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function insurancePolicy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }
}
