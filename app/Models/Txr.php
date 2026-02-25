<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class Txr extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes, Userstamps;

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

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
