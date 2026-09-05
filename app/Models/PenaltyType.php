<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PenaltyType extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'calculation_type',
        'default_rate',
        'is_active',
    ];

    protected $casts = [
        'default_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function appliedPenalties(): HasMany
    {
        return $this->hasMany(AppliedPenalty::class);
    }
}
