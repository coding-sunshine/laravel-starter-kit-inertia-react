<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $entity_type
 * @property int $entity_id
 * @property string $compliance_type
 * @property string $title
 * @property \Carbon\Carbon $expiry_date
 * @property string $status
 * @property bool $renewal_required
 * @property bool $legal_requirement
 */
class ComplianceItem extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'compliance_type',
        'title',
        'description',
        'regulatory_body',
        'legal_requirement',
        'issue_date',
        'expiry_date',
        'renewal_date',
        'status',
        'days_warning',
        'cost',
        'renewal_cost',
        'renewal_required',
        'auto_renewal_enabled',
        'reminder_frequency_days',
        'last_reminder_sent',
        'document_reference',
        'issuing_authority',
        'certificate_number',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'renewal_date' => 'date',
        'last_reminder_sent' => 'datetime',
        'legal_requirement' => 'boolean',
        'renewal_required' => 'boolean',
        'auto_renewal_enabled' => 'boolean',
        'cost' => 'decimal:2',
        'renewal_cost' => 'decimal:2',
    ];

    /**
     * Entity this compliance item belongs to (Vehicle, Driver, Organization, Trailer).
     *
     * @return MorphTo<Model, $this>
     */
    public function compliant(): MorphTo
    {
        return $this->morphTo(name: 'compliant', type: 'entity_type', id: 'entity_id');
    }
}
