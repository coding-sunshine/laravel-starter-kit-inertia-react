<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Siding;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class SidingsIndexTool extends Tool
{
    protected string $name = 'sidings_index';

    protected string $title = 'List Railway Sidings';

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        List all railway sidings in the system with their location, station code, and operational status.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $sidings = Siding::query()
            ->select('id', 'name', 'code', 'location', 'station_code', 'is_active', 'created_at')
            ->orderBy('name')
            ->get();

        $data = $sidings->map(fn ($siding): array => [
            'id' => $siding->id,
            'name' => $siding->name,
            'code' => $siding->code,
            'location' => $siding->location,
            'station_code' => $siding->station_code,
            'is_active' => $siding->is_active,
            'created_at' => $siding->created_at->toIso8601String(),
        ])->all();

        return Response::json([
            'data' => $data,
            'total' => count($data),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            //
        ];
    }
}
