<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RakeWagonLoading extends Model
{
    protected $table = 'wagon_loading';

    protected $fillable = [
        'rake_id',
        'wagon_id',
        'loader_id',
        'loader_operator_name',
        'cc_capacity_mt',
        'loaded_quantity_mt',
        'loading_time',
        'remarks',
    ];

    protected $casts = [
        'cc_capacity_mt' => 'decimal:2',
        'loaded_quantity_mt' => 'decimal:2',
        'loading_time' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function wagon(): BelongsTo
    {
        return $this->belongsTo(Wagon::class);
    }

    public function loader(): BelongsTo
    {
        return $this->belongsTo(Loader::class);
    }
}
