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
    use InteractsWithMedia, Userstamps;

    protected $fillable = [
        'rake_id',
        'weighment_time',
        'total_weight_mt',
        'average_wagon_weight_mt',
        'weighment_status',
        'remarks',
    ];

    protected $casts = [
        'weighment_time' => 'datetime',
    ];

    protected $appends = ['weighment_slip_url'];

    public function getWeighmentSlipUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('weighment_slip_pdf');

        return $media ? $media->getUrl() : null;
    }

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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
