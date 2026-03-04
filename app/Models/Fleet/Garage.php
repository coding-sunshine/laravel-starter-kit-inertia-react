<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $type
 * @property array|null $specializations
 * @property string|null $address
 * @property string|null $postcode
 * @property string|null $city
 * @property float|null $lat
 * @property float|null $lng
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property array|null $operating_hours
 * @property int $capacity
 * @property string|null $certification_level
 * @property float|null $hourly_rate
 * @property bool $preferred_supplier
 * @property float|null $quality_rating
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Garage extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'name',
        'type',
        'specializations',
        'address',
        'postcode',
        'city',
        'lat',
        'lng',
        'contact_name',
        'contact_phone',
        'contact_email',
        'operating_hours',
        'capacity',
        'certification_level',
        'hourly_rate',
        'preferred_supplier',
        'quality_rating',
        'is_active',
    ];

    protected $casts = [
        'specializations' => 'array',
        'operating_hours' => 'array',
        'capacity' => 'integer',
        'hourly_rate' => 'decimal:2',
        'quality_rating' => 'decimal:2',
        'preferred_supplier' => 'boolean',
        'is_active' => 'boolean',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
    ];
}
