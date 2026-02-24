<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RakeWagonLoading extends Model
{
    protected $table = 'rake_wagon_loading';

    protected $fillable = [
        'rake_load_id',
        'wagon_id',
        'loader_id',
        'loaded_quantity_mt',
        'attempt_no',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'loaded_quantity_mt' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function rakeLoad(): BelongsTo
    {
        return $this->belongsTo(RakeLoad::class);
    }

    public function wagon(): BelongsTo
    {
        return $this->belongsTo(Wagon::class);
    }

    public function loader(): BelongsTo
    {
        return $this->belongsTo(Loader::class);
    }

    public function weighments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RakeWagonWeighment::class);
    }
}
