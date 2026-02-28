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
 * @property string|null $code
 * @property string|null $contractor_type
 * @property string $status
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property string|null $address
 * @property string|null $postcode
 * @property string|null $city
 * @property string|null $tax_number
 * @property string|null $insurance_reference
 * @property \Carbon\Carbon|null $insurance_expiry
 * @property string|null $notes
 * @property bool $is_active
 */
class Contractor extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'name',
        'code',
        'contractor_type',
        'status',
        'contact_name',
        'contact_phone',
        'contact_email',
        'address',
        'postcode',
        'city',
        'tax_number',
        'insurance_reference',
        'insurance_expiry',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'insurance_expiry' => 'date',
        'is_active' => 'boolean',
    ];

    public function contractorCompliance(): HasMany
    {
        return $this->hasMany(ContractorCompliance::class, 'contractor_id');
    }

    public function contractorInvoices(): HasMany
    {
        return $this->hasMany(ContractorInvoice::class, 'contractor_id');
    }
}
