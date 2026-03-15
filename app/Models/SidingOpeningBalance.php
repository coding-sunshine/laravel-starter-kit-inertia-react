<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SidingOpeningBalance extends Model
{
    protected $table = 'siding_opening_balances';

    protected $fillable = [
        'siding_id',
        'opening_balance_mt',
        'as_of_date',
        'remarks',
    ];

    protected $casts = [
        'opening_balance_mt' => 'decimal:2',
        'as_of_date' => 'date',
    ];

    public static function getOpeningBalanceForSiding(int $sidingId): float
    {
        $value = self::query()
            ->where('siding_id', $sidingId)
            ->value('opening_balance_mt');

        return $value !== null ? (float) $value : 0.0;
    }

    /**
     * @return BelongsTo<Siding, $this>
     */
    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }
}
