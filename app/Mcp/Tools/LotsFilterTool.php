<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Lot;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class LotsFilterTool extends Tool
{
    protected string $name = 'lots_filter';

    protected string $title = 'Filter lots';

    protected string $description = 'Find lots matching buyer criteria (budget, bedrooms, suburb). Returns PropertyCard data for C1 generative UI rendering.';

    public function handle(Request $request): Response
    {
        $maxPrice = $request->get('max_price');
        $minPrice = $request->get('min_price');
        $bedrooms = $request->get('bedrooms');
        $suburb = $request->get('suburb');
        $status = (string) ($request->get('status') ?? 'available');
        $limit = (int) ($request->get('limit') ?? 5);

        $q = Lot::query()
            ->with('project')
            ->where('is_archived', false)
            ->where('title_status', $status)
            ->limit(min($limit, 20));

        if ($maxPrice !== null) {
            $q->where('price', '<=', (float) $maxPrice);
        }

        if ($minPrice !== null) {
            $q->where('price', '>=', (float) $minPrice);
        }

        if ($bedrooms !== null) {
            $q->where('bedrooms', (int) $bedrooms);
        }

        if ($suburb !== null) {
            $q->whereHas('project', fn ($b) => $b->where('suburb', 'ilike', "%{$suburb}%"));
        }

        $lots = $q->get();
        $items = $lots->map(fn (Lot $lot) => [
            'id' => $lot->id,
            'type' => 'lot',
            'title' => $lot->title ?? "Lot #{$lot->id}",
            'suburb' => $lot->project?->suburb,
            'state' => $lot->project?->state,
            'title_status' => $lot->title_status,
            'price' => $lot->price !== null ? (float) $lot->price : null,
            'bedrooms' => $lot->bedrooms,
            'bathrooms' => $lot->bathrooms,
            'car' => $lot->car,
            'total_m2' => $lot->total !== null ? (float) $lot->total : null,
            'project_title' => $lot->project?->title,
            'is_hot_property' => $lot->project?->is_hot_property ?? false,
            'actions' => [
                ['label' => 'View Project', 'type' => 'link', 'href' => "/projects/{$lot->project_id}"],
                ['label' => 'Reserve', 'type' => 'action', 'action' => 'start_reservation', 'payload' => ['lot_id' => $lot->id]],
            ],
        ])->all();

        return Response::json([
            'component' => 'PropertyCard',
            'multiple' => true,
            'count' => count($items),
            'items' => $items,
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'max_price' => $schema->number()->description('Maximum price in AUD')->nullable(),
            'min_price' => $schema->number()->description('Minimum price in AUD')->nullable(),
            'bedrooms' => $schema->integer()->description('Number of bedrooms')->nullable(),
            'suburb' => $schema->string()->description('Suburb or area name')->nullable(),
            'status' => $schema->string()->description('Lot status: available, reserved, sold')->nullable(),
            'limit' => $schema->integer()->description('Max results (1-20, default 5)')->nullable(),
        ];
    }
}
