<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class WagonType extends Model
{
    protected $fillable = [
        'code',
        'full_form',
        'typical_use',
        'loading_method',
        'carrying_capacity_min_mt',
        'carrying_capacity_max_mt',
        'gross_tare_weight_mt',
        'default_pcc_weight_mt',
    ];

    protected function casts(): array
    {
        return [
            'carrying_capacity_min_mt' => 'decimal:2',
            'carrying_capacity_max_mt' => 'decimal:2',
            'gross_tare_weight_mt' => 'decimal:2',
            'default_pcc_weight_mt' => 'decimal:2',
        ];
    }
}
