<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class Indent extends Model
{
    use SoftDeletes, Userstamps;

    protected $fillable = [
        'siding_id',
        'indent_number',
        'target_quantity_mt',
        'allocated_quantity_mt',
        'state',
        'indent_date',
        'required_by_date',
        'remarks',
    ];

    protected $casts = [
        'indent_date' => 'datetime',
        'required_by_date' => 'datetime',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
