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

    /**
     * Net / actual dispatch weight from parsed RR (FOIS `ACTL WGHT`), persisted in {@see $rr_details}.
     */
    public function actualWeightMtFromDetails(): ?string
    {
        $details = $this->rr_details;
        if (! is_array($details)) {
            return null;
        }
        $raw = $details['actual_weight_mt'] ?? null;
        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
            return null;
        }

        return (string) round((float) $raw, 4);
    }

    /**
     * Actual / net weight for UI lists: sum of wagon snapshot loaded weights (parsed ACTL per wagon), with
     * fallback to header ACTL {@see actualWeightMtFromDetails} when snapshots are missing or have no loads,
     * then to the sender/RR weight ({@see $rr_weight_mt}) when actual weight is absent or zero.
     */
    public function actualWeightMtForListing(): ?string
    {
        $this->loadMissing('wagonSnapshots:id,rr_document_id,loaded_weight_mt');

        $sum = 0.0;
        $hasLoaded = false;
        foreach ($this->wagonSnapshots as $row) {
            if ($row->loaded_weight_mt === null) {
                continue;
            }
            $hasLoaded = true;
            $sum += (float) $row->loaded_weight_mt;
        }

        if ($hasLoaded && $sum > 0) {
            return (string) round($sum, 4);
        }

        $fromDetails = $this->actualWeightMtFromDetails();
        if ($fromDetails !== null && (float) $fromDetails > 0) {
            return $fromDetails;
        }

        if ($this->rr_weight_mt !== null && is_numeric($this->rr_weight_mt) && (float) $this->rr_weight_mt > 0) {
            return (string) round((float) $this->rr_weight_mt, 4);
        }

        return $fromDetails;
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
