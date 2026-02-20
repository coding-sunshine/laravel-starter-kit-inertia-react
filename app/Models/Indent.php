<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Indent extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes, Userstamps;

    protected $fillable = [
        'siding_id',
        'indent_number',
        'target_quantity_mt',
        'allocated_quantity_mt',
        'state',
        'indent_date',
        'required_by_date',
        'remarks',
        'e_demand_reference_id',
        'fnr_number',
    ];

    protected $casts = [
        'indent_date' => 'datetime',
        'required_by_date' => 'datetime',
    ];

    protected $appends = ['indent_confirmation_pdf_url'];

    public function getIndentConfirmationPdfUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('indent_confirmation_pdf');

        return $media ? $media->getUrl() : null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('indent_confirmation_pdf')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
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
