<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $user_id
 * @property string|null $employee_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property \Carbon\Carbon|null $date_of_birth
 * @property \Carbon\Carbon|null $hire_date
 * @property \Carbon\Carbon|null $termination_date
 * @property string $status
 * @property string $license_number
 * @property \Carbon\Carbon $license_expiry_date
 * @property string $license_status
 * @property array|null $license_categories
 * @property string|null $cpc_number
 * @property \Carbon\Carbon|null $cpc_expiry_date
 * @property \Carbon\Carbon|null $medical_certificate_expiry
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $address
 * @property string|null $postcode
 * @property float $safety_score
 * @property string $risk_category
 * @property int $accidents_count
 * @property int $violations_count
 * @property int $training_completed_count
 * @property \Carbon\Carbon|null $last_incident_date
 * @property string $compliance_status
 * @property \Carbon\Carbon|null $last_dvla_check
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Carbon\Carbon|null $deleted_at
 */
class Driver extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'user_id',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'hire_date',
        'termination_date',
        'status',
        'license_number',
        'license_expiry_date',
        'license_status',
        'license_categories',
        'cpc_number',
        'cpc_expiry_date',
        'medical_certificate_expiry',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'postcode',
        'safety_score',
        'risk_category',
        'accidents_count',
        'violations_count',
        'training_completed_count',
        'last_incident_date',
        'compliance_status',
        'last_dvla_check',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'license_expiry_date' => 'date',
        'cpc_expiry_date' => 'date',
        'medical_certificate_expiry' => 'date',
        'last_incident_date' => 'date',
        'last_dvla_check' => 'date',
        'license_categories' => 'array',
        'safety_score' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Vehicle, $this>
     */
    public function vehiclesAssigned(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'current_driver_id');
    }

    /**
     * @return HasMany<DriverVehicleAssignment, $this>
     */
    public function vehicleAssignments(): HasMany
    {
        return $this->hasMany(DriverVehicleAssignment::class);
    }

    /**
     * Current assignment (is_current = true), if any.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<DriverVehicleAssignment, $this>
     */
    public function currentAssignment()
    {
        return $this->hasOne(DriverVehicleAssignment::class)->where('is_current', true);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
