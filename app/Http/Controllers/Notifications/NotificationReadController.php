<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationReadController extends Controller
{
    public function markAll(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['ok' => false], 401);
        }

        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function markOne(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['ok' => false], 401);
        }

        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification === null) {
            return response()->json(['ok' => false], 404);
        }

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }
}
