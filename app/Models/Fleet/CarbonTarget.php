<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $description
 * @property string $period
 * @property int $target_year
 * @property float $target_co2_kg
 * @property float|null $baseline_co2_kg
 * @property bool $is_active
 */
final class CarbonTarget extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'name', 'description', 'period', 'target_year', 'target_co2_kg',
        'baseline_co2_kg', 'is_active',
    ];

    protected $casts = [
        'target_co2_kg' => 'decimal:2',
        'baseline_co2_kg' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
