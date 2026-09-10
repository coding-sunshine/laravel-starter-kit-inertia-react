<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a data-quality anomaly detected during Loadrite event ingestion.
 *
 * Records cases where auto-correction failed (e.g. wagon_type could not be
 * normalised, operator name was unmappable, or event_time was bogus). A manager
 * can review and resolve these rows via the admin panel.
 *
 * @property int $id
 * @property int $siding_id
 * @property int|null $loadrite_event_id
 * @property string $kind wagon_type_unmappable|operator_unmappable|bogus_timestamp|rake_serial_missing
 * @property string|null $raw_value
 * @property array|null $context
 * @property string $status open|resolved|ignored
 * @property \Carbon\Carbon|null $resolved_at
 * @property int|null $resolved_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class LoadriteAnomaly extends Model
{
    use HasFactory;

    protected $fillable = [
        'siding_id',
        'loadrite_event_id',
        'kind',
        'raw_value',
        'context',
        'status',
        'resolved_at',
        'resolved_by',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function loadriteEvent(): BelongsTo
    {
        return $this->belongsTo(LoadriteEvent::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'resolved_at' => 'datetime',
        ];
    }
}
