<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $contact_id
 * @property string|null $call_sid
 * @property string $direction
 * @property int $duration_seconds
 * @property string|null $transcript
 * @property string|null $sentiment
 * @property string|null $outcome
 * @property array|null $vapi_metadata
 * @property \Carbon\Carbon $called_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class CallLog extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'contact_id',
        'call_sid',
        'direction',
        'duration_seconds',
        'transcript',
        'sentiment',
        'outcome',
        'vapi_metadata',
        'called_at',
    ];

    public function casts(): array
    {
        return [
            'vapi_metadata' => 'array',
            'called_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
