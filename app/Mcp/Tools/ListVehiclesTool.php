<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Fleet\Vehicle;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class ListVehiclesTool extends Tool
{
    protected string $name = 'fleet_list_vehicles';

    protected string $title = 'List fleet vehicles';

    protected string $description = <<<'MARKDOWN'
        List vehicles for the authenticated user's organization. Requires organization context (uses user's default org).
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return Response::json(['error' => 'Unauthenticated']);
        }

        $org = $user->defaultOrganization() ?? $user->organizations()->first();
        if ($org === null) {
            return Response::json(['data' => [], 'message' => 'User has no organization']);
        }

        TenantContext::set($org);

        $query = Vehicle::query()->with(['homeLocation:id,name']);

        $registration = $request->get('filter_registration');
        if (is_string($registration) && $registration !== '') {
            $query->where('registration', 'like', '%'.$registration.'%');
        }
        $status = $request->get('filter_status');
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $sort = $request->get('sort', '-created_at');
        if (is_string($sort) && $sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $query->orderBy(mb_ltrim($sort, '-'), $direction);
        }

        $perPage = (int) $request->get('per_page', 15);
        $vehicles = $query->paginate($perPage);

        $data = [
            'data' => $vehicles->map(fn (Vehicle $v): array => [
                'id' => $v->id,
                'registration' => $v->registration,
                'make' => $v->make,
                'model' => $v->model,
                'year' => $v->year,
                'status' => $v->status,
                'fuel_type' => $v->fuel_type,
            ])->all(),
            'meta' => [
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
            ],
        ];

        return Response::json($data);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filter_registration' => $schema->string()->description('Partial match on registration')->nullable(),
            'filter_status' => $schema->string()->description('Filter by status')->nullable(),
            'sort' => $schema->string()->description('Sort column, prefix with - for desc (e.g. -created_at)')->nullable(),
            'per_page' => $schema->integer()->description('Items per page')->nullable(),
        ];
    }
}
