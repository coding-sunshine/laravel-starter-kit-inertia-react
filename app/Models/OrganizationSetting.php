<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Key-value overrides per organization (group.name → payload).
 * Used by OrganizationSettingsService; table: organization_settings.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $group
 * @property string $name
 * @property array $payload
 * @property bool $is_encrypted
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 */
final class OrganizationSetting extends Model
{
    protected $table = 'organization_settings';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'group',
        'name',
        'payload',
        'is_encrypted',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'is_encrypted' => 'boolean',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
