<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class GeocodingService
{
    private const string GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    private const int CACHE_TTL_SECONDS = 86400; // 24 hours

    /**
     * Reverse geocode lat/lng to a human-readable address.
     * Returns null if API key is missing, request fails, or no result.
     */
    public function reverseGeocode(float $lat, float $lng): ?string
    {
        $key = config('services.google.maps_api_key', env('VITE_GOOGLE_MAPS_API_KEY'));
        if (empty($key) || ! is_string($key)) {
            return null;
        }

        $cacheKey = 'geocode:'.round($lat, 4).':'.round($lng, 4);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($lat, $lng, $key): ?string {
            $response = Http::get(self::GEOCODE_URL, [
                'latlng' => $lat.','.$lng,
                'key' => $key,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            if (($data['status'] ?? '') !== 'OK') {
                return null;
            }

            $results = $data['results'] ?? [];
            $first = $results[0] ?? null;
            if ($first === null) {
                return null;
            }

            return $first['formatted_address'] ?? null;
        });
    }
}
