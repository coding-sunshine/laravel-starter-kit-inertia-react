<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property \Carbon\Carbon|null $target_date
 * @property float|null $target_value
 * @property string|null $target_unit
 */
final class SustainabilityGoal extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'title', 'description', 'status', 'target_date', 'target_value', 'target_unit', 'metrics',
    ];

    protected $casts = [
        'target_date' => 'date',
        'target_value' => 'decimal:2',
        'metrics' => 'array',
    ];
}
