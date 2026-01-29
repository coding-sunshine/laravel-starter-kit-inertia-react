<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

final class EmbeddingDemo extends Model
{
    use HasNeighbors;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = ['content', 'embedding'];

    protected function casts(): array
    {
        return [
            'embedding' => Vector::class,
        ];
    }
}
