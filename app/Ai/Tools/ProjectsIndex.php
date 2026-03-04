<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class ProjectsIndex implements Tool
{
    public function description(): string
    {
        return 'Search or list projects (developments) in the current organization. Use query to search by title or description; omit for recently updated.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = $request->string('query')->trim();
        $limit = min(max((int) $request->integer('limit', 10), 1), 50);

        $builder = Project::query()
            ->orderByDesc('updated_at')
            ->limit($limit);

        if ($query !== '') {
            $like = '%'.Str::replace(['%', '_'], ['\\%', '\\_'], $query).'%';
            $builder->where(function ($q) use ($like): void {
                $q->where('title', 'ILIKE', $like)->orWhere('description', 'ILIKE', $like);
            });
        }

        $projects = $builder->get(['id', 'organization_id', 'title', 'stage', 'estate', 'total_lots', 'min_price', 'max_price', 'updated_at']);

        if ($projects->isEmpty()) {
            return 'No projects found.';
        }

        $lines = $projects->map(fn (Project $p): string => sprintf(
            'ID %d: %s | Stage: %s | Estate: %s | Lots: %s | Price: %s–%s | Updated: %s',
            $p->id,
            Str::limit($p->title, 50),
            $p->stage ?? '—',
            $p->estate ?? '—',
            $p->total_lots ?? '—',
            $p->min_price ? number_format((float) $p->min_price) : '—',
            $p->max_price ? number_format((float) $p->max_price) : '—',
            $p->updated_at?->toDateString() ?? '—',
        ));

        return "Projects (up to {$limit}):\n\n".$lines->implode("\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Optional search for title or description.')->default(''),
            'limit' => $schema->integer()->description('Max results (1–50).')->default(10),
        ];
    }
}
