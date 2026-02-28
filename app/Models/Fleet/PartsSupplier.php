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
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property string|null $address
 * @property string|null $postcode
 * @property string|null $city
 * @property string|null $payment_terms
 * @property float|null $minimum_order_value
 * @property bool $preferred
 * @property bool $is_active
 */
class PartsSupplier extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'parts_suppliers';

    protected $fillable = [
        'name',
        'code',
        'contact_name',
        'contact_phone',
        'contact_email',
        'address',
        'postcode',
        'city',
        'payment_terms',
        'minimum_order_value',
        'preferred',
        'is_active',
    ];

    protected $casts = [
        'minimum_order_value' => 'decimal:2',
        'preferred' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function partsInventory(): HasMany
    {
        return $this->hasMany(PartsInventory::class, 'supplier_id');
    }
}
