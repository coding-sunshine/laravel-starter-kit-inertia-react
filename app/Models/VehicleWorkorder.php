<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class VehicleWorkorder extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'vehicle_workorders';

    protected $guarded = [];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function registerMediaCollections(): void
    {
        $docMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        $this->addMediaCollection('vehicle_rc')
            ->singleFile()
            ->acceptsMimeTypes($docMimes);

        $this->addMediaCollection('vehicle_insurance')
            ->singleFile()
            ->acceptsMimeTypes($docMimes);

        $this->addMediaCollection('vehicle_other_documents')
            ->acceptsMimeTypes($docMimes);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tyres' => 'integer',
            'work_order_date' => 'date',
            'issued_date' => 'date',
            'regd_date' => 'date',
            'permit_validity_date' => 'date',
            'tax_validity_date' => 'date',
            'fitness_validity_date' => 'date',
            'insurance_validity_date' => 'date',
        ];
    }
}
