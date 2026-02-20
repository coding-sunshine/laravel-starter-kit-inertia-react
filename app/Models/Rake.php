<?php

declare(strict_types=1);

namespace App\Models;

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
        'rake_number',
        'rake_type',
        'wagon_count',
        'loading_start_time',
        'loading_end_time',
        'loaded_weight_mt',
        'predicted_weight_mt',
        'state',
        'free_time_minutes',
        'demurrage_hours',
        'demurrage_penalty_amount',
        'rr_expected_date',
        'rr_actual_date',
    ];

    protected $casts = [
        'loading_start_time' => 'datetime',
        'loading_end_time' => 'datetime',
        'rr_expected_date' => 'datetime',
        'rr_actual_date' => 'datetime',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function wagons(): HasMany
    {
        return $this->hasMany(Wagon::class);
    }

    public function weighments(): HasMany
    {
        return $this->hasMany(Weighment::class);
    }

    public function txr(): HasOne
    {
        return $this->hasOne(Txr::class);
    }

    public function guardInspection(): HasOne
    {
        return $this->hasOne(GuardInspection::class);
    }

    public function rrDocuments(): HasMany
    {
        return $this->hasMany(RrDocument::class);
    }

    public function rrPrediction(): HasOne
    {
        return $this->hasOne(RrPrediction::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class);
    }

    public function powerPlantReceipts(): HasMany
    {
        return $this->hasMany(PowerPlantReceipt::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
