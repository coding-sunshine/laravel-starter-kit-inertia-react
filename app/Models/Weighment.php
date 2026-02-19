<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;

final class Weighment extends Model
{
    use Userstamps;

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

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
