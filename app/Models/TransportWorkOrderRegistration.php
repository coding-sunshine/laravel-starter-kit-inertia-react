<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class TransportWorkOrderRegistration extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\TransportWorkOrderRegistrationFactory> */
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    use InteractsWithMedia;

    public const string GRAMIN_OR_NON_GRAMIN_GRAMIN = 'gramin';

    public const string GRAMIN_OR_NON_GRAMIN_NON_GRAMIN = 'non_gramin';

    /**
     * @var list<string>
     */
    public const array GRAMIN_OR_NON_GRAMIN_VALUES = [
        self::GRAMIN_OR_NON_GRAMIN_GRAMIN,
        self::GRAMIN_OR_NON_GRAMIN_NON_GRAMIN,
    ];

    protected $table = 'transport_work_order_registrations';

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

        $this->addMediaCollection('pan_documents')
            ->acceptsMimeTypes($docMimes);

        $this->addMediaCollection('gst_documents')
            ->acceptsMimeTypes($docMimes);

        $this->addMediaCollection('transporter_documents')
            ->acceptsMimeTypes($docMimes);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_order_date' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
