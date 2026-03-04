<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $driver_id
 * @property \Carbon\Carbon $download_date
 * @property string|null $file_path
 * @property string $status
 * @property \Carbon\Carbon|null $analysed_at
 */
final class TachographDownload extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'driver_id',
        'download_date',
        'file_path',
        'status',
        'analysed_at',
        'notes',
    ];

    protected $casts = [
        'download_date' => 'date',
        'analysed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
