<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $policy_number
 * @property string $insurer_name
 * @property string $policy_type
 * @property string $coverage_type
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property float|null $premium_amount
 * @property string $status
 */
final class InsurancePolicy extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'policy_number', 'insurer_name', 'policy_type', 'coverage_type',
        'start_date', 'end_date', 'premium_amount', 'excess_amount', 'no_claims_bonus_years',
        'covered_vehicles', 'covered_drivers', 'coverage_limits', 'exclusions',
        'broker_name', 'broker_contact', 'policy_documents', 'auto_renewal', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'premium_amount' => 'decimal:2',
        'excess_amount' => 'decimal:2',
        'covered_vehicles' => 'array',
        'covered_drivers' => 'array',
        'coverage_limits' => 'array',
        'exclusions' => 'array',
        'policy_documents' => 'array',
        'auto_renewal' => 'boolean',
    ];

    /**
     * @return HasMany<InsuranceClaim, $this>
     */
    public function insuranceClaims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class, 'insurance_policy_id');
    }
}
