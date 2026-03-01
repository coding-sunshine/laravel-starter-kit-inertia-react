'use client';

import { useJsApiLoader } from '@react-google-maps/api';
import type { ReactNode } from 'react';
import { createContext, useMemo, useContext } from 'react';

const GOOGLE_MAPS_SCRIPT_ID = 'google-maps-fleet-script';

export interface GoogleMapsContextValue {
    isLoaded: boolean;
    loadError: Error | undefined;
    apiKeyConfigured: boolean;
}

const GoogleMapsContext = createContext<GoogleMapsContextValue | null>(null);

const apiKey = (import.meta.env.VITE_GOOGLE_MAPS_API_KEY as string | undefined)?.trim() || undefined;

export function GoogleMapsProvider({ children }: { children: ReactNode }): ReactNode {
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

    return (
        <GoogleMapsContext.Provider value={value}>
            {children}
        </GoogleMapsContext.Provider>
    );
}

export function useGoogleMaps(): GoogleMapsContextValue {
    const context = useContext(GoogleMapsContext);
    if (context == null) {
        throw new Error('useGoogleMaps must be used inside GoogleMapsProvider');
    }
    return context;
}
