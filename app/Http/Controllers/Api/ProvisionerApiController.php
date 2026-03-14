<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\WordpressSiteProvisioned;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WordpressWebsite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProvisionerApiController extends Controller
{
    public function pending(): JsonResponse
    {
        $sites = WordpressWebsite::query()
            ->where('stage', 1)
            ->get();

        return response()->json($sites);
    }

    public function removing(): JsonResponse
    {
        $sites = WordpressWebsite::query()
            ->where('stage', 4)
            ->get();

        return response()->json($sites);
    }

    public function callback(Request $request, WordpressWebsite $site): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'integer'],
            'instance_id' => ['nullable', 'string', 'max:255'],
            'url_key' => ['nullable', 'string', 'max:255'],
            'wp_username' => ['nullable', 'string', 'max:255'],
            'wp_password' => ['nullable', 'string', 'max:255'],
            'active_url' => ['nullable', 'string', 'max:255'],
        ]);

        $site->update($validated);

        $ownerId = $site->organization?->owner_id;

        if ($ownerId) {
            broadcast(new WordpressSiteProvisioned($site))->toOthers();
        }

        return response()->json(['success' => true]);
    }

    public function subscriberDetail(string $api_key): JsonResponse
    {
        $user = User::query()
            ->where('api_token', $api_key)
            ->firstOrFail();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'organization' => $user->currentOrganization?->only(['id', 'name']),
        ]);
    }
}
