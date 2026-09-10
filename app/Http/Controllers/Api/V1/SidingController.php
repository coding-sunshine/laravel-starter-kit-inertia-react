<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Siding;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SidingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        /** @var Collection<int, Siding> $sidings */
        if ($user->isSuperAdmin()) {
            $query = Siding::query()->orderBy('name');
            if ($request->boolean('active_only')) {
                $query->where('is_active', true);
            }
            $sidings = $query->get();
        } else {
            $relation = $user->sidings()->orderBy('sidings.name');
            if ($request->boolean('active_only')) {
                $relation->where('sidings.is_active', true);
            }
            $sidings = $relation->get();
        }

        $data = $sidings->map(fn (Siding $siding): array => [
            'id' => $siding->id,
            'name' => $siding->name,
            'code' => $siding->code,
            'location' => $siding->location,
            'station_code' => $siding->station_code,
            'is_active' => $siding->is_active,
        ])->values()->all();

        return response()->json([
            'data' => $data,
        ]);
    }
}
