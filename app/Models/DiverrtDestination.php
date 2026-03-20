<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DiverrtDestination extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'diverrt_destination';

    protected $fillable = [
        'rake_id',
        'location',
        'rr_number',
        'stt_no',
        'srr_date',
        'data_source',
    ];

    protected $casts = [
        'srr_date' => 'date',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }
}
