<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $alert_type
 * @property string $severity
 * @property string $title
 * @property string $description
 * @property string|null $entity_type
 * @property int|null $entity_id
 * @property \Carbon\Carbon $triggered_at
 * @property \Carbon\Carbon|null $acknowledged_at
 * @property int|null $acknowledged_by
 * @property \Carbon\Carbon|null $resolved_at
 * @property int|null $resolved_by
 * @property string|null $resolution_notes
 * @property string $status
 * @property bool $notification_sent
 * @property int $escalation_level
 * @property \Carbon\Carbon|null $escalated_at
 * @property \Carbon\Carbon|null $auto_resolve_at
 * @property array|null $metadata
 */
class Alert extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'alert_type',
        'severity',
        'title',
        'description',
        'entity_type',
        'entity_id',
        'triggered_at',
        'acknowledged_at',
        'acknowledged_by',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'status',
        'notification_sent',
        'escalation_level',
        'escalated_at',
        'auto_resolve_at',
        'metadata',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'notification_sent' => 'boolean',
        'escalation_level' => 'integer',
        'escalated_at' => 'datetime',
        'auto_resolve_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
