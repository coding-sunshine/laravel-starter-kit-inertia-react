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
        'pdf_file_path',
        'status',
        'created_by',
    ];

    protected $casts = [
        'gross_weighment_datetime' => 'datetime',
        'tare_weighment_datetime' => 'datetime',
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

    protected function getWeighmentSlipUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('weighment_slip_pdf');

        return $media instanceof \Spatie\MediaLibrary\MediaCollections\Models\Media ? $media->getUrl() : null;
    }
}
