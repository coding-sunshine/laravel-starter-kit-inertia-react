<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class RrDocument extends Model implements HasMedia
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use InteractsWithMedia, Userstamps;

    protected $fillable = [
        'rake_id',
        'diverrt_destination_id',
        'rr_number',
        'rr_received_date',
        'rr_weight_mt',
        'fnr',
        'from_station_code',
        'to_station_code',
        'freight_total',
        'distance_km',
        'commodity_code',
        'commodity_description',
        'invoice_number',
        'invoice_date',
        'rate',
        'class',
        'rr_details',
        'document_status',
        'data_source',
        'has_discrepancy',
        'discrepancy_details',
        'created_by',
        'updated_by',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('rr_pdf')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function diverrtDestination(): BelongsTo
    {
        return $this->belongsTo(DiverrtDestination::class, 'diverrt_destination_id');
    }

    public function rrCharges(): HasMany
    {
        return $this->hasMany(RrCharge::class);
    }

    public function wagonSnapshots(): HasMany
    {
        return $this->hasMany(RrWagonSnapshot::class);
    }

    public function penaltySnapshots(): HasMany
    {
        return $this->hasMany(RrPenaltySnapshot::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return [
            'rr_received_date' => 'datetime',
            'invoice_date' => 'date',
            'has_discrepancy' => 'boolean',
            'rr_details' => 'array',
            'freight_total' => 'decimal:2',
            'distance_km' => 'decimal:2',
            'rate' => 'decimal:2',
        ];
    }
}
