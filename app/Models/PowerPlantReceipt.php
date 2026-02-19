<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PowerPlantReceipt extends Model
{
    protected $fillable = [
        'rake_id',
        'power_plant_id',
        'receipt_date',
        'weight_mt',
        'rr_reference',
        'variance_mt',
        'variance_pct',
        'status',
        'created_by',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function powerPlant(): BelongsTo
    {
        return $this->belongsTo(PowerPlant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
        ];
    }
}
