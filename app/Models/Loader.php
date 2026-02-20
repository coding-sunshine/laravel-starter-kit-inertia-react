<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Loader extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'siding_id',
        'loader_name',
        'code',
        'loader_type',
        'make_model',
        'last_calibration_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_calibration_date' => 'date',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function wagons(): HasMany
    {
        return $this->hasMany(Wagon::class);
    }

    public function loaderPerformance(): HasMany
    {
        return $this->hasMany(LoaderPerformance::class);
    }
}
