<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Integrations\Loadrite\LoadriteConnector;
use App\Http\Integrations\Loadrite\Requests\RefreshTokenRequest;
use App\Models\LoadriteSetting;
use Carbon\CarbonInterface;

final class LoadriteTokenManager
{
    public function getConnector(?int $sidingId = null): LoadriteConnector
    {
        $setting = $this->getOrFail($sidingId);

        if ($this->isExpired($setting)) {
            $setting = $this->refresh($setting);
        }

        return new LoadriteConnector($setting->access_token);
    }

    public function refresh(LoadriteSetting $setting): LoadriteSetting
    {
        $connector = new LoadriteConnector($setting->access_token);
        $response = $connector->send(new RefreshTokenRequest($setting->access_token, $setting->refresh_token));

        $data = $response->json();

        $setting->update([
            'access_token' => $data['accessToken'],
            'refresh_token' => $data['refreshToken'],
            'expires_at' => now()->addSeconds((int) $data['accessTokenExpiryInSeconds']),
        ]);

        return $setting->fresh();
    }

    public function store(?int $sidingId, string $siteName, string $accessToken, string $refreshToken, CarbonInterface $expiresAt): LoadriteSetting
    {
        return LoadriteSetting::updateOrCreate(
            ['siding_id' => $sidingId],
            [
                'site_name' => $siteName,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => $expiresAt,
            ],
        );
    }

    private function getOrFail(?int $sidingId): LoadriteSetting
    {
        return LoadriteSetting::query()
            ->where('siding_id', $sidingId)
            ->firstOrFail();
    }

    private function isExpired(LoadriteSetting $setting): bool
    {
        return $setting->expires_at->isPast();
    }
}
