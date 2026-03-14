<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $summarizable_type
 * @property int $summarizable_id
 * @property string $content
 * @property string $model
 * @property \Carbon\Carbon $created_at
 */
final class AiSummary extends Model
{
    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'summarizable_type',
        'summarizable_id',
        'content',
        'model',
        'created_at',
    ];

    public function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function summarizable(): MorphTo
    {
        return $this->morphTo();
    }
}
