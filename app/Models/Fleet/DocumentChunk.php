<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property string $chunkable_type
 * @property int $chunkable_id
 * @property string $content
 * @property array|null $metadata
 * @property array|null $embedding
 * @property \Pgvector\Laravel\Vector|null $embedding_vector
 */
class DocumentChunk extends Model
{
    use BelongsToOrganization;
    use HasNeighbors;

    protected $fillable = [
        'chunkable_type',
        'chunkable_id',
        'content',
        'metadata',
        'embedding',
        'embedding_vector',
    ];

    protected $casts = [
        'metadata' => 'array',
        'embedding' => 'array',
        'embedding_vector' => Vector::class,
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function chunkable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Column to use for vector similarity search (pgvector). Falls back to 'embedding' json when not on PostgreSQL.
     */
    public function vectorColumn(): string
    {
        return \Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'embedding_vector')
            ? 'embedding_vector'
            : 'embedding';
    }
}
