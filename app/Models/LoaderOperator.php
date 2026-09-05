<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoaderOperator extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'siding_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }
}
