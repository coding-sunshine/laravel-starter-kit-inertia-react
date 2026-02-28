<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $chunkable_type
 * @property int $chunkable_id
 * @property string $content
 * @property array|null $metadata
 * @property array|null $embedding
 */
class DocumentChunk extends Model
{
    protected $fillable = ['chunkable_type', 'chunkable_id', 'content', 'metadata', 'embedding'];

    protected $casts = [
        'metadata' => 'array',
        'embedding' => 'array',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function chunkable(): MorphTo
    {
        return $this->morphTo();
    }
}
