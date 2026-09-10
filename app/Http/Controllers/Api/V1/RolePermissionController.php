<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

final class RolePermissionController extends Controller
{
    public function index(Role $role): JsonResponse
    {
        $permissions = $role->permissions()
            ->select('id', 'name')
            ->get();

        return response()->json([
            'data' => [
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                ],
                'permissions' => $permissions,
            ],
        ]);
    }
}
