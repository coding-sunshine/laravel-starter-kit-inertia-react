<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Txr extends Model implements HasMedia
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use Userstamps;
    use InteractsWithMedia;

    protected $table = 'txr';

    protected $fillable = [
        'rake_id',
        'inspection_time',
        'inspection_end_time',
        'status',
        'remarks',
    ];

    protected $casts = [
        'inspection_time' => 'datetime',
        'inspection_end_time' => 'datetime',
    ];

    protected $appends = ['handwritten_note_url'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('txr_note')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
            ]);
    }

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function wagonUnfitLogs(): HasMany
    {
        return $this->hasMany(WagonUnfitLog::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function getHandwrittenNoteUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('txr_note');

        return $media instanceof \Spatie\MediaLibrary\MediaCollections\Models\Media
            ? $media->getUrl()
            : null;
    }
}
