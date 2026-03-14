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
 * @property int $score
 * @property array|null $factors_json
 * @property string $model_version
 * @property \Carbon\Carbon $scored_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class LeadScore extends Model
{
    use HasFactory;
    use LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'contact_id',
        'score',
        'factors_json',
        'model_version',
        'scored_at',
    ];

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
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
            'factors_json' => 'array',
            'scored_at' => 'datetime',
        ];
    }
}
