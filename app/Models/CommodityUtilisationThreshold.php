<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

final class CommodityUtilisationThreshold extends Model
{
    use HasFactory;
    use Userstamps;

    protected $fillable = [
        'commodity_grade',
        'utilisation_threshold',
        'effective_from',
        'effective_to',
        'source',
    ];

    protected $casts = [
        'utilisation_threshold' => 'decimal:3',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    public static function activeFor(string $commodityGrade, ?DateTimeInterface $at = null): ?self
    {
        $at ??= now();

        return self::query()
            ->where('commodity_grade', $commodityGrade)
            ->where('effective_from', '<=', $at)
            ->where(function ($q) use ($at) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $at);
            })
            ->orderByDesc('effective_from')
            ->first();
    }
}
