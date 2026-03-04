'use client';

import { useJsApiLoader } from '@react-google-maps/api';
import type { ReactNode } from 'react';
import { createContext, use, useMemo } from 'react';

const GOOGLE_MAPS_SCRIPT_ID = 'google-maps-fleet-script';

export interface GoogleMapsContextValue {
    isLoaded: boolean;
    loadError: Error | undefined;
    apiKeyConfigured: boolean;
}

const GoogleMapsContext = createContext<GoogleMapsContextValue | null>(null);

const apiKey =
    (import.meta.env.VITE_GOOGLE_MAPS_API_KEY as string | undefined)?.trim() ||
    undefined;

export function GoogleMapsProvider({
    children,
}: {
    children: ReactNode;
}): ReactNode {
    const { isLoaded, loadError } = useJsApiLoader({
        id: GOOGLE_MAPS_SCRIPT_ID,
        googleMapsApiKey: apiKey ?? '',
        preventGoogleFontsLoading: true,
    });

    const value = useMemo<GoogleMapsContextValue>(
        () => ({
            isLoaded: isLoaded === true && !!apiKey,
            loadError: loadError ?? undefined,
            apiKeyConfigured: !!apiKey,
        }),
        [isLoaded, loadError],
    );

    return <GoogleMapsContext value={value}>{children}</GoogleMapsContext>;
}

export function useGoogleMaps(): GoogleMapsContextValue {
    const context = use(GoogleMapsContext);
    if (context == null) {
        throw new Error('useGoogleMaps must be used inside GoogleMapsProvider');
    }
    return context;
}
