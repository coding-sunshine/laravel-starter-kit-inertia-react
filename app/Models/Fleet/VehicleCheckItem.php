<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class VehicleCheckItem extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'vehicle_check_id',
        'item_index',
        'label',
        'result_type',
        'result',
        'value_text',
        'photo_media_id',
        'notes',
    ];

    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }
}
