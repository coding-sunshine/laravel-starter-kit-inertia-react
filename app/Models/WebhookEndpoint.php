<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $url
 * @property array $events
 * @property string|null $secret
 * @property bool $is_active
 * @property \Carbon\Carbon|null $last_triggered_at
 * @property int $failure_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class WebhookEndpoint extends Model
{
    use BelongsToOrganization;
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'url',
        'events',
        'secret',
        'is_active',
        'last_triggered_at',
        'failure_count',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['secret']);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
        ];
    }
}
