<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\IndentController;
use App\Http\Controllers\Api\V1\RailwayReceiptApiController;
use App\Http\Controllers\Api\V1\RailwayReceiptUploadController;
use App\Http\Controllers\Api\V1\RakeController;
use App\Http\Controllers\Api\V1\RakeWeighmentApiController;
use App\Http\Controllers\Api\V1\RolePermissionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WeighmentUploadController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): JsonResponse => response()->json([
    'name' => config('app.name'),
    'version' => config('scramble.info.version'),
    'message' => 'API documentation is at /docs/api. Versioned API base is /api/v1.',
]))->name('api');

Route::prefix('v1')->name('api.v1.')->middleware('throttle:60,1')->group(function (): void {
    Route::get('/', fn (): JsonResponse => response()->json([
        'name' => config('app.name'),
        'version' => config('scramble.info.version'),
        'message' => 'API documentation is available at /docs/api',
    ]))->name('info');

    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware(['auth:sanctum', 'feature:api_access'])->group(function (): void {
        Route::post('auth/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users/batch', [UserController::class, 'batch'])->name('users.batch');
        Route::post('users/search', [UserController::class, 'search'])->name('users.search');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        // Indents
        Route::get('indents', [IndentController::class, 'index'])->name('indents.index');
        Route::post('indents/upload', [IndentController::class, 'upload'])->name('indents.upload');
        Route::get('indents/{indent}', [IndentController::class, 'show'])->name('indents.show');
        Route::get('indents/{indent}/download', [IndentController::class, 'download'])->name('indents.download');

        // Railway receipts
        Route::post('railway-receipts/upload', [RailwayReceiptUploadController::class, 'store'])->name('railway-receipts.upload');
        Route::get('railway-receipts', [RailwayReceiptApiController::class, 'index'])->name('railway-receipts.index');
        Route::get('railway-receipts/{rrDocument}', [RailwayReceiptApiController::class, 'show'])->name('railway-receipts.show');
        Route::get('railway-receipts/{rrDocument}/download', [RailwayReceiptApiController::class, 'download'])->name('railway-receipts.download');

        // Weighments
        Route::post('weighments/upload', [WeighmentUploadController::class, 'store'])->name('weighments.upload');

        Route::get('roles/{role}/permissions', [RolePermissionController::class, 'index'])->name('roles.permissions.index');

        // Rakes
        Route::get('rakes', [RakeController::class, 'index'])->name('rakes.index');
        Route::get('rakes/export', [RakeController::class, 'export'])->name('rakes.export');
        Route::get('rakes/{rake}', [RakeController::class, 'show'])->name('rakes.show');

        // Rake weighments
        Route::get('rake-weighments', [RakeWeighmentApiController::class, 'index'])->name('rake-weighments.index');
        Route::get('rake-weighments/{rakeWeighment}', [RakeWeighmentApiController::class, 'show'])->name('rake-weighments.show');
        Route::get('rake-weighments/{rakeWeighment}/download', [RakeWeighmentApiController::class, 'download'])->name('rake-weighments.download');
    });
});
