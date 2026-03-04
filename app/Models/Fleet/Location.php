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
 * @property string $name
 * @property string $type
 * @property string $address
 * @property string|null $postcode
 * @property string|null $city
 * @property string $country
 * @property float|null $lat
 * @property float|null $lng
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property array|null $operating_hours
 * @property string|null $access_restrictions
 * @property string|null $notes
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Location extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'name',
        'type',
        'address',
        'postcode',
        'city',
        'country',
        'lat',
        'lng',
        'contact_name',
        'contact_phone',
        'contact_email',
        'operating_hours',
        'access_restrictions',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'operating_hours' => 'array',
        'is_active' => 'boolean',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
    ];

    /**
     * @return HasMany<Vehicle, $this>
     */
    public function vehiclesHomeLocation(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'home_location_id');
    }

    /**
     * @return HasMany<Geofence, $this>
     */
    public function geofences(): HasMany
    {
        return $this->hasMany(Geofence::class, 'location_id');
    }

    /**
     * @return HasMany<Trailer, $this>
     */
    public function trailers(): HasMany
    {
        return $this->hasMany(Trailer::class, 'home_location_id');
    }
}
