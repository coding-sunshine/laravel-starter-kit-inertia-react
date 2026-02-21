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
        'responsible_party',
        'root_cause',
        'description',
        'remediation_notes',
        'disputed_at',
        'dispute_reason',
        'resolved_at',
        'resolution_notes',
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
            'disputed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'calculation_breakdown' => 'array',
        ];
    }
}
