<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Wagon extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'rake_id',
        'wagon_sequence',
        'wagon_number',
        'wagon_type',
        'tare_weight_mt',
        'loaded_weight_mt',
        'pcc_weight_mt',
        'loader_recorded_qty_mt',
        'weighment_qty_mt',
        'is_unfit',
        'state',
    ];

    protected $casts = [
        'is_unfit' => 'boolean',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function wagonWeighments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RakeWagonWeighment::class);
    }
}
