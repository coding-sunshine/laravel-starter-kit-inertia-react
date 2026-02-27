<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $license_number
 * @property string $license_type
 * @property string $traffic_commissioner_area
 * @property \Carbon\Carbon $issue_date
 * @property \Carbon\Carbon $effective_date
 * @property \Carbon\Carbon $expiry_date
 * @property \Carbon\Carbon|null $last_review_date
 * @property \Carbon\Carbon|null $next_review_date
 * @property int $authorized_vehicles
 * @property int $authorized_vehicles_used
 * @property int $authorized_trailers
 * @property int $authorized_trailers_used
 * @property array $operating_centres
 * @property float|null $financial_requirement_amount
 * @property string|null $financial_evidence_type
 * @property \Carbon\Carbon|null $financial_evidence_expiry
 * @property string|null $transport_manager_name
 * @property string|null $transport_manager_cpc_number
 * @property string|null $transport_manager_contact
 * @property string|null $compliance_rating
 * @property string|null $repute_status
 * @property array|null $conditions_attached
 * @property array|null $undertakings
 * @property string $status
 * @property \Carbon\Carbon|null $last_compliance_inspection_date
 * @property \Carbon\Carbon|null $next_compliance_inspection_due
 * @property string|null $maintenance_intervals
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Carbon\Carbon|null $deleted_at
 */
class OperatorLicence extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'license_number',
        'license_type',
        'traffic_commissioner_area',
        'issue_date',
        'effective_date',
        'expiry_date',
        'last_review_date',
        'next_review_date',
        'authorized_vehicles',
        'authorized_vehicles_used',
        'authorized_trailers',
        'authorized_trailers_used',
        'operating_centres',
        'financial_requirement_amount',
        'financial_evidence_type',
        'financial_evidence_expiry',
        'transport_manager_name',
        'transport_manager_cpc_number',
        'transport_manager_contact',
        'compliance_rating',
        'repute_status',
        'conditions_attached',
        'undertakings',
        'status',
        'last_compliance_inspection_date',
        'next_compliance_inspection_due',
        'maintenance_intervals',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'last_review_date' => 'date',
        'next_review_date' => 'date',
        'financial_evidence_expiry' => 'date',
        'last_compliance_inspection_date' => 'date',
        'next_compliance_inspection_due' => 'date',
        'operating_centres' => 'array',
        'conditions_attached' => 'array',
        'undertakings' => 'array',
        'authorized_vehicles' => 'integer',
        'authorized_vehicles_used' => 'integer',
        'authorized_trailers' => 'integer',
        'authorized_trailers_used' => 'integer',
        'financial_requirement_amount' => 'decimal:2',
    ];
}
