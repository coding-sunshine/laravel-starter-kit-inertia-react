<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

final class EmbeddingDocument extends Model
{
    use BelongsToOrganization;
    use HasNeighbors;

    protected $fillable = [
        'organization_id',
        'document_type',
        'document_id',
        'content',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => Vector::class,
        ];
    }
}
