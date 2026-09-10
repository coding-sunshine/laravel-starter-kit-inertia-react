<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class SidingShift extends Model
{
    protected $fillable = [
        'siding_id',
        'shift_name',
        'start_time',
        'end_time',
        'is_active',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
    ];

    /**
     * @return BelongsTo<Siding, $this>
     */
    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'siding_shift_user')
            ->withPivot(['assigned_at', 'is_active'])
            ->withTimestamps();
    }
}
