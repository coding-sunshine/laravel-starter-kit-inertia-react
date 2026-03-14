<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ChatMemoryController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V2;
use App\Http\Controllers\WebsiteController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): JsonResponse => response()->json([
    'name' => config('app.name'),
    'version' => config('scramble.info.version'),
    'message' => 'API documentation is at /docs/api. Versioned API base is /api/v1.',
]))->name('api');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('chat', ChatController::class)->name('api.chat');
    Route::get('chat/memories', ChatMemoryController::class)->name('chat.memories');
    Route::get('conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('conversations/{id}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::patch('conversations/{id}', [ConversationController::class, 'update'])->name('conversations.update');
    Route::delete('conversations/{id}', [ConversationController::class, 'destroy'])->name('conversations.destroy');
});

Route::prefix('v2')->name('api.v2.')->middleware(['throttle:api', 'auth:sanctum', 'tenant'])->group(function (): void {
    // Contacts
    Route::get('contacts', [V2\ContactController::class, 'index'])->name('contacts.index');
    Route::post('contacts', [V2\ContactController::class, 'store'])->name('contacts.store');
    Route::get('contacts/{contact}', [V2\ContactController::class, 'show'])->name('contacts.show');
    Route::match(['put', 'patch'], 'contacts/{contact}', [V2\ContactController::class, 'update'])->name('contacts.update');
    // Projects
    Route::get('projects', [V2\ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/{project}', [V2\ProjectController::class, 'show'])->name('projects.show');
    // Lots
    Route::get('lots', [V2\LotController::class, 'index'])->name('lots.index');
    Route::get('lots/{lot}', [V2\LotController::class, 'show'])->name('lots.show');
    // Sales
    Route::get('sales', [V2\SaleController::class, 'index'])->name('sales.index');
    Route::post('sales', [V2\SaleController::class, 'store'])->name('sales.store');
    Route::get('sales/{sale}', [V2\SaleController::class, 'show'])->name('sales.show');
    Route::match(['put', 'patch'], 'sales/{sale}', [V2\SaleController::class, 'update'])->name('sales.update');
    // Tasks
    Route::get('tasks', [V2\TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [V2\TaskController::class, 'store'])->name('tasks.store');
    Route::get('tasks/{task}', [V2\TaskController::class, 'show'])->name('tasks.show');
    Route::match(['put', 'patch'], 'tasks/{task}', [V2\TaskController::class, 'update'])->name('tasks.update');
    // Reservations
    Route::get('reservations', [V2\ReservationController::class, 'index'])->name('reservations.index');
    Route::post('reservations', [V2\ReservationController::class, 'store'])->name('reservations.store');
    Route::get('reservations/{reservation}', [V2\ReservationController::class, 'show'])->name('reservations.show');
    Route::match(['put', 'patch'], 'reservations/{reservation}', [V2\ReservationController::class, 'update'])->name('reservations.update');
    // Webhooks
    Route::get('webhooks', [V2\WebhookController::class, 'index'])->name('webhooks.index');
    Route::post('webhooks', [V2\WebhookController::class, 'store'])->name('webhooks.store');
    Route::get('webhooks/{webhook}', [V2\WebhookController::class, 'show'])->name('webhooks.show');
    Route::match(['put', 'patch'], 'webhooks/{webhook}', [V2\WebhookController::class, 'update'])->name('webhooks.update');
    Route::delete('webhooks/{webhook}', [V2\WebhookController::class, 'destroy'])->name('webhooks.destroy');
});

// WordPress provisioner callback (server-to-server, no user auth)
Route::patch('websites/{id}', [WebsiteController::class, 'provisionerCallback'])->name('api.websites.callback');

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
    });
});
