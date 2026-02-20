<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class RrDocument extends Model implements HasMedia
{
    use InteractsWithMedia, Userstamps;

    protected $fillable = [
        'rake_id',
        'rr_number',
        'rr_received_date',
        'rr_weight_mt',
        'fnr',
        'from_station_code',
        'to_station_code',
        'freight_total',
        'rr_details',
        'document_status',
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
            'has_discrepancy' => 'boolean',
            'rr_details' => 'array',
            'freight_total' => 'decimal:2',
        ];
    }
}
