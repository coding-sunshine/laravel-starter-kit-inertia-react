<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PowerPlant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PowerPlantApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $query = PowerPlant::query()->orderBy('name');

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        /** @var Collection<int, PowerPlant> $powerPlants */
        $powerPlants = $query->get();

        $data = $powerPlants->map(fn (PowerPlant $plant): array => [
            'id' => $plant->id,
            'name' => $plant->name,
            'code' => $plant->code,
            'location' => $plant->location,
            'is_active' => $plant->is_active,
        ])->values()->all();

        return response()->json([
            'data' => $data,
        ]);
    }
}
