<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;

final class GuardInspection extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use Userstamps;

    protected $fillable = [
        'rake_id',
        'rake_load_id',
        'attempt_no',
        'inspection_time',
        'movement_permission_time',
        'is_approved',
        'remarks',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rakeLoad(): BelongsTo
    {
        return $this->belongsTo(RakeLoad::class);
    }

    protected function casts(): array
    {
        return [
            'inspection_time' => 'datetime',
            'movement_permission_time' => 'datetime',
            'is_approved' => 'boolean',
        ];
    }
}
