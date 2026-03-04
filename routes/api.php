<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BulkDocumentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): JsonResponse => response()->json([
    'name' => config('app.name'),
    'version' => config('scramble.info.version'),
    'message' => 'API documentation is at /docs/api. Versioned API base is /api/v1.',
]))->name('api');

// Chat & conversations: served under web auth (same session as /chat page) in web.php to avoid Sanctum stateful SPA issues.

Route::prefix('v1')->name('api.v1.')->middleware('throttle:60,1')->group(function (): void {
    Route::get('/', fn (): JsonResponse => response()->json([
        'name' => config('app.name'),
        'version' => config('scramble.info.version'),
        'message' => 'API documentation is available at /docs/api',
    ]))->name('info');

    Route::middleware(['auth:sanctum', 'feature:api_access'])->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users/batch', [UserController::class, 'batch'])->name('users.batch');
        Route::post('users/search', [UserController::class, 'search'])->name('users.search');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Dashboard routes
        Route::get('dashboard/metrics', [DashboardController::class, 'metrics'])->name('dashboard.metrics');
        Route::get('dashboard/pipeline', [DashboardController::class, 'pipeline'])->name('dashboard.pipeline');
        Route::get('dashboard/revenue', [DashboardController::class, 'revenue'])->name('dashboard.revenue');
        Route::get('dashboard/distribution', [DashboardController::class, 'distribution'])->name('dashboard.distribution');

        // Bulk Document Processing routes
        Route::prefix('documents')->name('documents.')->group(function (): void {
            Route::post('bulk-upload', [BulkDocumentController::class, 'upload'])->name('bulk-upload');
            Route::get('batch/{batchId}/status', [BulkDocumentController::class, 'batchStatus'])->name('batch-status');
            Route::get('queue-status', [BulkDocumentController::class, 'queueStatus'])->name('queue-status');
            Route::post('bulk-approve', [BulkDocumentController::class, 'bulkApprove'])->name('bulk-approve');
            Route::post('bulk-reject', [BulkDocumentController::class, 'bulkReject'])->name('bulk-reject');
        });
    });
});
