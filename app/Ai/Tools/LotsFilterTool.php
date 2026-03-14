<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Lot;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * AI tool: filter lots by buyer criteria and return C1-compatible PropertyCard data.
 */
final class LotsFilterTool implements Tool
{
    public function description(): string
    {
        return 'Find lots matching buyer criteria (budget, bedrooms, suburb). Returns PropertyCard data for C1 rendering.';
    }

    public function handle(Request $request): Stringable|string
    {
        $maxPrice = $request->input['max_price'] ?? null;
        $minPrice = $request->input['min_price'] ?? null;
        $bedrooms = $request->input['bedrooms'] ?? null;
        $suburb = $request->input['suburb'] ?? null;
        $status = $request->input['status'] ?? 'available';
        $limit = (int) ($request->input['limit'] ?? 5);

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

        $result = [
            'component' => 'PropertyCard',
            'multiple' => true,
            'count' => count($items),
            'items' => $items,
        ];

        return Str::of(json_encode($result) ?: '{}');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'max_price' => $schema->number()->description('Maximum price in AUD'),
            'min_price' => $schema->number()->description('Minimum price in AUD'),
            'bedrooms' => $schema->integer()->description('Number of bedrooms'),
            'suburb' => $schema->string()->description('Suburb or area name'),
            'status' => $schema->string()->description('Lot status: available, reserved, sold (default: available)'),
            'limit' => $schema->integer()->description('Max results (1-20, default 5)'),
        ];
    }
}
