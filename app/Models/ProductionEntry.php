<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductionEntry extends Model
{
    public const TYPE_COAL = 'coal';

    public const TYPE_OB = 'ob';

    protected $table = 'production_entries';

    protected $fillable = [
        'type',
        'date',
        'trip',
        'qty',
        'siding_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => 'string',
        'date' => 'date',
        'trip' => 'string',
        'qty' => 'decimal:2',
        'siding_id' => 'integer',
    ];

    public function siding(): BelongsTo
    {
        return $this->belongsTo(Siding::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
