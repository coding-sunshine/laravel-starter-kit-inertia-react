<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Penalty extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'rake_id',
        'penalty_type',
        'penalty_amount',
        'penalty_status',
        'description',
        'remediation_notes',
        'penalty_date',
        'calculation_breakdown',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    protected function casts(): array
    {
        return [
            'penalty_date' => 'date',
            'calculation_breakdown' => 'array',
        ];
    }
}
