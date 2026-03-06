<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Weighment extends Model implements HasMedia
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use InteractsWithMedia, Userstamps;

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
        'updated_by',  // required by Userstamps trait
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

    protected $appends = ['weighment_slip_url'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('weighment_slip_pdf')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function rakeWagonWeighments(): HasMany
    {
        return $this->hasMany(RakeWagonWeighment::class, 'rake_weighment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function getWeighmentSlipUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('weighment_slip_pdf');

        return $media instanceof \Spatie\MediaLibrary\MediaCollections\Models\Media
            ? $media->getUrl()
            : null;
    }
}