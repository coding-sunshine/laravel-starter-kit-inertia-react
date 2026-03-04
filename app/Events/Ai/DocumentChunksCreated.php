<?php

declare(strict_types=1);

namespace App\Events\Ai;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class DocumentChunksCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Media $media,
        public int $organizationId,
        public int $chunkCount,
    ) {}
}
