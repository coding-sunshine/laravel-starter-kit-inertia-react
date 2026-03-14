<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $credits_total
 * @property int $credits_used
 * @property \Carbon\Carbon $period_start
 * @property \Carbon\Carbon $period_end
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class AiCreditPool extends Model
{
    protected $fillable = [
        'organization_id',
        'credits_total',
        'credits_used',
        'period_start',
        'period_end',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function remaining(): int
    {
        return max(0, $this->credits_total - $this->credits_used);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }
}
