<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RakeWeighment extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'rake_weighments';

    protected $fillable = [
        'rake_id',
        'attempt_no',
        'gross_weighment_datetime',
        'tare_weighment_datetime',
        'train_name',
        'direction',
        'commodity',
        'from_station',
        'to_station',
        'priority_number',
        'total_gross_weight_mt',
        'total_tare_weight_mt',
        'total_net_weight_mt',
        'total_cc_weight_mt',
        'total_under_load_mt',
        'total_over_load_mt',
        'maximum_train_speed_kmph',
        'maximum_weight_mt',
        'pdf_file_path',
        'status',
        'created_by',
    ];

    protected $casts = [
        'gross_weighment_datetime' => 'datetime',
        'tare_weighment_datetime' => 'datetime',
        'total_gross_weight_mt' => 'decimal:2',
        'total_tare_weight_mt' => 'decimal:2',
        'total_net_weight_mt' => 'decimal:2',
        'total_cc_weight_mt' => 'decimal:2',
        'total_under_load_mt' => 'decimal:2',
        'total_over_load_mt' => 'decimal:2',
        'maximum_train_speed_kmph' => 'decimal:2',
        'maximum_weight_mt' => 'decimal:2',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function rakeWagonWeighments(): HasMany
    {
        return $this->hasMany(RakeWagonWeighment::class);
    }
}
