<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Wagon extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'rake_id',
        'wagon_sequence',
        'wagon_number',
        'wagon_type',
        'tare_weight_mt',
        'pcc_weight_mt',
        'loaded_weight_mt',
        'permissible_weight_mt',
        'overload_weight_mt',
        'is_unfit',
        'state',
    ];

    protected $casts = [
        'tare_weight_mt' => 'decimal:2',
        'pcc_weight_mt' => 'decimal:2',
        'is_unfit' => 'boolean',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function wagonLoadings(): HasMany
    {
        return $this->hasMany(WagonLoading::class);
    }

    public function rakeWagonWeighments(): HasMany
    {
        return $this->hasMany(RakeWagonWeighment::class);
    }

    public function wagonUnfitLogs(): HasMany
    {
        return $this->hasMany(WagonUnfitLog::class);
    }
}
