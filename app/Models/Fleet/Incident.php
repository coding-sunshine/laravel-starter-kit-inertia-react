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
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $vehicle_id
 * @property int|null $driver_id
 * @property string $incident_number
 * @property string $incident_type
 * @property string $severity
 * @property string $status
 */
final class Incident extends Model implements HasMedia
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'vehicle_id', 'driver_id', 'incident_number', 'incident_date', 'incident_time', 'incident_timestamp',
        'incident_type', 'severity', 'location_description', 'lat', 'lng',
        'weather_conditions', 'road_conditions', 'traffic_conditions', 'fault_determination',
        'police_attended', 'police_reference', 'injuries_reported', 'injury_count',
        'third_party_involved', 'third_party_details', 'witnesses',
        'description', 'initial_assessment', 'estimated_damage_cost', 'actual_repair_cost',
        'vehicle_driveable', 'recovery_required', 'recovery_cost', 'status',
        'reported_by', 'investigating_officer',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'incident_timestamp' => 'datetime',
        'third_party_details' => 'array',
        'witnesses' => 'array',
        'estimated_damage_cost' => 'decimal:2',
        'actual_repair_cost' => 'decimal:2',
        'recovery_cost' => 'decimal:2',
        'vehicle_driveable' => 'boolean',
        'recovery_required' => 'boolean',
        'police_attended' => 'boolean',
        'injuries_reported' => 'boolean',
        'third_party_involved' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
        $this->addMediaCollection('documents');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function reportedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function investigatingOfficerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigating_officer');
    }

    /** @return HasMany<InsuranceClaim, $this> */
    public function insuranceClaims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }
}
