<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property string $name
 * @property string $type
 * @property string|null $description
 * @property array $config
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class FunnelTemplate extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'description',
        'config',
        'is_active',
    ];

    public function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<FunnelInstance, $this>
     */
    public function instances(): HasMany
    {
        return $this->hasMany(FunnelInstance::class);
    }
}
