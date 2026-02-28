<?php

declare(strict_types=1);

namespace App\Listeners\Ai;

use App\Jobs\Ai\ChunkAndEmbedMediaJob;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

final class ChunkAndEmbedMediaOnUpload
{
    /** Collections that should be indexed for RAG (per model). */
    private const INDEXABLE_COLLECTIONS = ['documents', 'photos'];

    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;
        if (! in_array($media->collection_name, self::INDEXABLE_COLLECTIONS, true)) {
            return;
        }

        $model = $media->model;
        if (! $model || ! isset($model->organization_id)) {
            return;
        }

        ChunkAndEmbedMediaJob::dispatch($media->id, $model->organization_id);
    }
}
