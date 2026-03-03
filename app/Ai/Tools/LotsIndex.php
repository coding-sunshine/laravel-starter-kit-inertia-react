<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Lot;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class LotsIndex implements Tool
{
    public function description(): string
    {
        return 'Search or list lots in the current organization. Optionally filter by project_id or search by title.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = $request->string('query')->trim();
        $projectId = $request->integer('project_id', 0) ?: null;
        $limit = min(max((int) $request->integer('limit', 10), 1), 50);

        $builder = Lot::query()
            ->whereHas('project')
            ->with('project:id,title')
            ->orderByDesc('updated_at')
            ->limit($limit);

        if ($projectId > 0) {
            $builder->where('project_id', $projectId);
        }
        if ($query !== '') {
            $like = '%'.Str::replace(['%', '_'], ['\\%', '\\_'], $query).'%';
            $builder->where('title', 'like', $like);
        }

        $lots = $builder->get(['id', 'project_id', 'title', 'stage', 'price', 'bedrooms', 'bathrooms', 'updated_at']);

        if ($lots->isEmpty()) {
            return 'No lots found.';
        }

        $lines = $lots->map(fn (Lot $l): string => sprintf(
            'ID %d: %s | Project: %s | Stage: %s | Price: %s | Beds: %s | Baths: %s | Updated: %s',
            $l->id,
            Str::limit($l->title ?? '—', 40),
            $l->relationLoaded('project') ? Str::limit($l->project?->title ?? '—', 25) : '—',
            $l->stage ?? '—',
            $l->price ? number_format($l->price) : '—',
            $l->bedrooms ?? '—',
            $l->bathrooms ?? '—',
            $l->updated_at?->toDateString() ?? '—',
        ));

        return "Lots (up to {$limit}):\n\n".$lines->implode("\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Optional search by lot title.')->default(''),
            'project_id' => $schema->integer()->description('Optional project ID to filter lots.')->default(0),
            'limit' => $schema->integer()->description('Max results (1–50).')->default(10),
        ];
    }
}
