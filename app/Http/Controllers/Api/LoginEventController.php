<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LoginEventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $fingerprint = $request->input('device_fingerprint');
        if (! is_string($fingerprint) || $fingerprint === '') {
            return response()->json(['ok' => false, 'error' => 'missing fingerprint'], 422);
        }

        $hash = hash('sha256', $fingerprint);

        // Find the most recent login event for this user within last 60 seconds
        $userId = $request->user()?->id;
        if ($userId !== null) {
            LoginEvent::query()
                ->where('user_id', $userId)
                ->where('created_at', '>=', now()->subSeconds(60))
                ->whereNull('device_fingerprint')
                ->latest()
                ->first()
                ?->update(['device_fingerprint' => $hash]);
        } else {
            // If not authenticated, find by IP
            LoginEvent::query()
                ->where('ip_address', $request->ip())
                ->where('created_at', '>=', now()->subSeconds(60))
                ->whereNull('device_fingerprint')
                ->latest()
                ->first()
                ?->update(['device_fingerprint' => $hash]);
        }

        return response()->json(['ok' => true]);
    }
}
