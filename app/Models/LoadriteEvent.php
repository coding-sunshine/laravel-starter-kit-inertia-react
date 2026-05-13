<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoadriteEvent extends Model
{
    use HasFactory;

    protected $table = 'loadrite_events';

    protected $fillable = [
        'event_id',
        'siding_id',
        'rake_id',
        'wagon_id',
        'wagon_sequence',
        'event_type',
        'weight_mt',
        'event_time',
        'operator',
        'scale_id',
        'product',
        'gps_lat',
        'gps_lng',
        'user_data1',
        'user_data2',
        'user_data3',
        'raw_payload',
    ];

    protected $casts = [
        'wagon_sequence' => 'integer',
        'weight_mt' => 'decimal:3',
        'event_time' => 'datetime',
        'gps_lat' => 'decimal:6',
        'gps_lng' => 'decimal:6',
        'raw_payload' => 'array',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function wagon(): BelongsTo
    {
        return $this->belongsTo(Wagon::class);
    }
}
