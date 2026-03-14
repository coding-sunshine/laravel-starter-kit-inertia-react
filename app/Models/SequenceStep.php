<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $nurture_sequence_id
 * @property int $step_order
 * @property string $channel
 * @property string|null $subject
 * @property string $template_body
 * @property int $delay_days
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class SequenceStep extends Model
{
    use HasFactory;
    use LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'nurture_sequence_id',
        'step_order',
        'channel',
        'subject',
        'template_body',
        'delay_days',
        'metadata',
    ];

    /**
     * @return BelongsTo<NurtureSequence, $this>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(NurtureSequence::class, 'nurture_sequence_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['embedding']);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
