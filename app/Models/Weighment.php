<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'rake_load_id',
        'weighment_time',
        'total_weight_mt',
        'status',
        'attempt_no',
        'train_speed_kmph',
        'remarks',
    ];

    protected $casts = [
        'weighment_time' => 'datetime',
        'total_weight_mt' => 'decimal:2',
        'train_speed_kmph' => 'decimal:2',
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

    public function rakeLoad(): BelongsTo
    {
        return $this->belongsTo(RakeLoad::class);
    }

    public function wagonWeighments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RakeWagonWeighment::class, 'rake_weighment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function getWeighmentSlipUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('weighment_slip_pdf');

        return $media instanceof \Spatie\MediaLibrary\MediaCollections\Models\Media ? $media->getUrl() : null;
    }
}
