<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): JsonResponse {
    return response()->json([
        'name' => config('app.name'),
        'version' => config('scramble.info.version'),
        'message' => 'API documentation is available at /docs/api',
    ]);
});
