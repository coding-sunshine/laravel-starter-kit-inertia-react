<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LoadriteDowntimeEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $siding_id
 * @property string $downtime_id
 * @property \Carbon\CarbonImmutable $start_local_time
 * @property \Carbon\CarbonImmutable|null $end_local_time
 * @property int|null $duration_minutes
 * @property string|null $reason_name
 * @property string|null $sub_reason_name
 * @property string|null $equipment_name
 * @property array $raw_payload
 */
final class LoadriteDowntimeEvent extends Model
{
    /** @use HasFactory<LoadriteDowntimeEventFactory> */
    use HasFactory;

    protected $fillable = [
        'siding_id',
        'downtime_id',
        'start_local_time',
        'end_local_time',
        'duration_minutes',
        'reason_name',
        'sub_reason_name',
        'equipment_name',
        'raw_payload',
    ];

    protected $casts = [
        'start_local_time' => 'immutable_datetime',
        'end_local_time' => 'immutable_datetime',
        'duration_minutes' => 'integer',
        'raw_payload' => 'array',
    ];

    /** @return BelongsTo<Siding, self> */
    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    protected static function newFactory(): LoadriteDowntimeEventFactory
    {
        return LoadriteDowntimeEventFactory::new();
    }
}
