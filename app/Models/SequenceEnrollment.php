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
 * @property int $contact_id
 * @property int $nurture_sequence_id
 * @property int $current_step
 * @property string $status
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon|null $next_run_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class SequenceEnrollment extends Model
{
    use HasFactory;
    use LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'contact_id',
        'nurture_sequence_id',
        'current_step',
        'status',
        'started_at',
        'next_run_at',
        'completed_at',
    ];

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

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
            'started_at' => 'datetime',
            'next_run_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
