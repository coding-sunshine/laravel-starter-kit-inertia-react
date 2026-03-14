<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $funnel_template_id
 * @property int $contact_id
 * @property string $status
 * @property int $current_step
 * @property array|null $data
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class FunnelInstance extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'funnel_template_id',
        'contact_id',
        'status',
        'current_step',
        'data',
        'started_at',
        'completed_at',
    ];

    public function casts(): array
    {
        return [
            'data' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FunnelTemplate::class, 'funnel_template_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
