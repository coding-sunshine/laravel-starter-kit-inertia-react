<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Indent extends Model implements HasMedia
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use InteractsWithMedia, SoftDeletes, Userstamps;

    protected $fillable = [
        'siding_id',
        'indent_number',
        'target_quantity_mt',
        'allocated_quantity_mt',
        'available_stock_mt',
        'state',
        'indent_date',
        'indent_time',
        'required_by_date',
        'remarks',
        'e_demand_reference_id',
        'fnr_number',
        'expected_loading_date',
        'demanded_stock',
        'total_units',
        'railway_reference_no',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'indent_date' => 'datetime',
        'indent_time' => 'datetime',
        'required_by_date' => 'datetime',
        'expected_loading_date' => 'datetime',
    ];

    protected $appends = ['indent_confirmation_pdf_url', 'indent_pdf_url'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('indent_confirmation_pdf')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
        $this->addMediaCollection('indent_pdf')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function rake(): HasOne
    {
        return $this->hasOne(Rake::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getIndentPdfUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('indent_pdf');
        if ($media instanceof \Spatie\MediaLibrary\MediaCollections\Models\Media) {
            return $media->getUrl();
        }

        return $this->indent_confirmation_pdf_url;
    }

    protected function getIndentConfirmationPdfUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('indent_confirmation_pdf');

        return $media instanceof \Spatie\MediaLibrary\MediaCollections\Models\Media ? $media->getUrl() : null;
    }
}
