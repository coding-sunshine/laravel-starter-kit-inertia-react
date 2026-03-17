<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class Rake extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes, Userstamps;

    protected $fillable = [
        'siding_id',
        'indent_id',
        'rake_number',
        'data_source',
        'rake_type',
        'wagon_count',
        'loaded_weight_mt',
        'predicted_weight_mt',

        // NEW HISTORICAL COLUMNS
        'loading_date',
        'priority_number',
        'destination_code',
        'under_load_mt',
        'over_load_mt',

        'state',
        'rr_expected_date',
        'rr_actual_date',
        'placement_time',
        'dispatch_time',
        'loading_start_time',
        'loading_end_time',
        'loading_free_minutes',
        'guard_start_time',
        'guard_end_time',
        'weighment_start_time',
        'weighment_end_time',
        'created_by',
        'updated_by',
        'deleted_by',
        'rr_number',
        'overload_wagon_count',
        'detention_hours',
        'shunting_hours',
        'total_amount_rs',
        'destination',
        'pakur_imwb_period',
        'remarks',
    ];

    protected $casts = [
        'placement_time' => 'datetime',
        'dispatch_time' => 'datetime',
        'rr_expected_date' => 'datetime',
        'rr_actual_date' => 'datetime',

        'loading_date' => 'date',          // NEW

        'loaded_weight_mt' => 'decimal:2',
        'predicted_weight_mt' => 'decimal:2',

        'under_load_mt' => 'decimal:2',    // NEW
        'over_load_mt' => 'decimal:2',     // NEW

        'priority_number' => 'integer',    // NEW

        'loading_start_time' => 'datetime',
        'loading_end_time' => 'datetime',
        'loading_free_minutes' => 'integer',
        'guard_start_time' => 'datetime',
        'guard_end_time' => 'datetime',
        'weighment_start_time' => 'datetime',
        'weighment_end_time' => 'datetime',

    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function indent(): BelongsTo
    {
        return $this->belongsTo(Indent::class);
    }

    public function wagons(): HasMany
    {
        return $this->hasMany(Wagon::class);
    }

    public function rakeWeighments(): HasMany
    {
        return $this->hasMany(RakeWeighment::class);
    }

    public function txr(): HasOne
    {
        return $this->hasOne(Txr::class);
    }

    public function wagonLoadings(): HasMany
    {
        return $this->hasMany(RakeWagonLoading::class);
    }

    public function rakeLoad(): HasOne
    {
        return $this->hasOne(RakeLoad::class);
    }

    public function guardInspections(): HasMany
    {
        return $this->hasMany(GuardInspection::class);
    }

    public function rrDocument(): HasOne
    {
        return $this->hasOne(RrDocument::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class);
    }

    public function appliedPenalties(): HasMany
    {
        return $this->hasMany(AppliedPenalty::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function rrDocumentId(): Attribute
    {
        return Attribute::get(fn () => $this->rrDocument?->id);
    }

    protected function pdfDownloadUrl(): Attribute
    {
        return Attribute::get(function () {
            $doc = $this->rrDocument;
            if (! $doc || ! $doc->hasMedia('rr_pdf')) {
                return null;
            }

            return route('railway-receipts.pdf', $doc);
        });
    }
}
