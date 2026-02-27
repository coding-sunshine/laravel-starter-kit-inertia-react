<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RakeWeighment extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'rake_weighments';

    protected $fillable = [
        'rake_id',
        'attempt_no',
        'gross_weighment_datetime',
        'tare_weighment_datetime',
        'train_name',
        'direction',
        'commodity',
        'from_station',
        'to_station',
        'priority_number',
        'pdf_file_path',
        'status',
        'created_by',
    ];

    protected $casts = [
        'gross_weighment_datetime' => 'datetime',
        'tare_weighment_datetime' => 'datetime',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function rakeWagonWeighments(): HasMany
    {
        return $this->hasMany(RakeWagonWeighment::class);
    }
}
