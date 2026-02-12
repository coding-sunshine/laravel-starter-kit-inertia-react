<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): JsonResponse {
    return response()->json([
        'name' => config('app.name'),
        'version' => config('scramble.info.version'),
        'message' => 'API documentation is at /docs/api. Versioned API base is /api/v1.',
    ]);
})->name('api');

Route::prefix('v1')->name('api.v1.')->middleware('throttle:60,1')->group(function (): void {
    Route::get('/', function (): JsonResponse {
        return response()->json([
            'name' => config('app.name'),
            'version' => config('scramble.info.version'),
            'message' => 'API documentation is available at /docs/api',
        ]);
    })->name('info');

    Route::middleware(['auth:sanctum', 'feature:api_access'])->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users/batch', [UserController::class, 'batch'])->name('users.batch');
        Route::post('users/search', [UserController::class, 'search'])->name('users.search');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
