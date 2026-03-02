<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Commission extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'commissionable_type',
        'commissionable_id',
        'commission_in',
        'commission_out',
        'commission_profit',
        'commission_percent_in',
        'commission_percent_out',
        'commission_percent_profit',
        'extra_attributes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'extra_attributes' => 'array',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function commissionable(): MorphTo
    {
        return $this->morphTo();
    }
}
