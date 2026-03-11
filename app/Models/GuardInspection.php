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
        'inspection_start_time',
        'inspection_end_time',
        'movement_permission_time',
        'is_approved',
        'remarks',
        'created_by',
        'updated_by',
    ];

    public function rake(): BelongsTo
    {
        return $this->belongsTo(Rake::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'inspection_start_time' => 'datetime',
            'inspection_end_time' => 'datetime',
            'movement_permission_time' => 'datetime',
            'is_approved' => 'boolean',
        ];
    }
}
