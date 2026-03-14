<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PotentialPropertyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property string $title
 * @property string|null $suburb
 * @property string|null $state
 * @property string|null $developer_name
 * @property string|null $description
 * @property float|null $estimated_price_min
 * @property float|null $estimated_price_max
 * @property string $status
 * @property bool $imported_from_csv
 * @property array|null $csv_row_data
 * @property int|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class PotentialProperty extends Model
{
    /** @use HasFactory<PotentialPropertyFactory> */
    use BelongsToOrganization;

    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'suburb',
        'state',
        'developer_name',
        'description',
        'estimated_price_min',
        'estimated_price_max',
        'status',
        'imported_from_csv',
        'csv_row_data',
        'created_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'imported_from_csv' => 'boolean',
            'csv_row_data' => 'array',
            'estimated_price_min' => 'decimal:2',
            'estimated_price_max' => 'decimal:2',
        ];
    }
}
