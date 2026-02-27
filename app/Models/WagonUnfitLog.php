<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WagonUnfitLog extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'txr_id',
        'wagon_id',
        'reason',
        'marking_method',
        'marked_at',
        'created_by',
    ];

    protected $casts = [
        'marked_at' => 'datetime',
    ];

    public function txr(): BelongsTo
    {
        return $this->belongsTo(Txr::class);
    }

    public function wagon(): BelongsTo
    {
        return $this->belongsTo(Wagon::class);
    }
}
