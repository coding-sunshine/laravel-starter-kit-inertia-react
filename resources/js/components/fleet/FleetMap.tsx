'use client';

import {
    GoogleMap as GoogleMapComponent,
    InfoWindow,
    Marker,
    Polygon,
    Polyline,
} from '@react-google-maps/api';
import type { ReactNode } from 'react';
import { useMemo } from 'react';
import { useGoogleMaps } from '@/providers/google-maps-provider';

/** Design system: primary #4348be, foreground #333333 */
export const FLEET_MAP_PRIMARY = '#4348be';
export const FLEET_MAP_POLYGON_FILL = 'rgba(67, 72, 190, 0.15)';

export interface LatLng {
    lat: number;
    lng: number;
}

export interface FleetMapProps {
    center: LatLng;
    zoom?: number;
    /** e.g. { width: '100%', height: '320px' } */
    mapContainerStyle?: { width: string; height: string };
    className?: string;
    children?: ReactNode;
}

const defaultContainerStyle: { width: string; height: string } = {
    width: '100%',
    height: '320px',
};

export function FleetMap({
    center,
    zoom = 12,
    mapContainerStyle = defaultContainerStyle,
    className,
    children,
}: FleetMapProps): ReactNode {
    const { isLoaded, loadError, apiKeyConfigured } = useGoogleMaps();

    const options = useMemo(
        () => ({
            zoomControl: true,
            mapTypeControl: true,
            scaleControl: true,
            streetViewControl: false,
            fullscreenControl: true,
        }),
        [],
    );

    const placeholderClass =
        'flex items-center justify-center rounded-lg border border-border text-sm';

    if (!apiKeyConfigured) {
        return (
            <div
                className={[className, placeholderClass, 'bg-muted/30 text-muted-foreground'].filter(Boolean).join(' ')}
                style={mapContainerStyle}
                role="status"
                aria-live="polite"
            >
                Map unavailable (no API key). Set VITE_GOOGLE_MAPS_API_KEY in .env
            </div>
        );
    }

    if (loadError) {
        return (
            <div
                className={[className, placeholderClass, 'bg-destructive/5 text-destructive'].filter(Boolean).join(' ')}
                style={mapContainerStyle}
                role="alert"
            >
                Map failed to load. Check your API key and network.
            </div>
        );
    }

    if (!isLoaded) {
        return (
            <div
                className={[className, placeholderClass, 'bg-muted/30 text-muted-foreground'].filter(Boolean).join(' ')}
                style={mapContainerStyle}
            >
                Loading map…
            </div>
        );
    }

    return (
        <div className={className}>
            <GoogleMapComponent
                mapContainerStyle={mapContainerStyle}
                center={center}
                zoom={zoom}
                options={options}
            >
                {children}
            </GoogleMapComponent>
        </div>
    );
}

/** Marker with optional label; use inside FleetMap. */
export interface FleetMapMarkerProps {
    position: LatLng;
    label?: string;
    title?: string;
    onClick?: () => void;
    children?: ReactNode;
}

export function FleetMapMarker({
    position,
    label,
    title,
    onClick,
    children,
}: FleetMapMarkerProps): ReactNode {
    return (
        <Marker
            position={position}
            label={label}
            title={title}
            onClick={onClick}
        >
            {children}
        </Marker>
    );
}

/** Polyline for route/trip path; primary color. */
export interface FleetMapPolylineProps {
    path: LatLng[];
}

export function FleetMapPolyline({ path }: FleetMapPolylineProps): ReactNode {
    const options = useMemo(
        () => ({
            path,
            strokeColor: FLEET_MAP_PRIMARY,
            strokeOpacity: 1,
            strokeWeight: 4,
        }),
        [path],
    );
    return <Polyline options={options} />;
}

/** Polygon for geofence; light primary fill, primary stroke. paths: single ring as LatLng[] or multiple rings as LatLng[][]. */
export interface FleetMapPolygonProps {
    paths: LatLng[] | LatLng[][];
}

export function FleetMapPolygon({ paths }: FleetMapPolygonProps): ReactNode {
    const normalizedPaths = useMemo(() => {
        if (paths.length === 0) return [];
        const first = paths[0];
        return typeof first === 'object' && first !== null && 'lat' in first
            ? [paths as LatLng[]]
            : (paths as LatLng[][]);
    }, [paths]);
    const options = useMemo(
        () => ({
            paths: normalizedPaths,
            fillColor: FLEET_MAP_PRIMARY,
            fillOpacity: 0.15,
            strokeColor: FLEET_MAP_PRIMARY,
            strokeWeight: 2,
        }),
        [normalizedPaths],
    );
    return <Polygon options={options} />;
}

/** Info window / popover; design system: white background, #333333 text. */
export interface FleetMapInfoWindowProps {
    position: LatLng;
    onCloseClick?: () => void;
    children: ReactNode;
}

export function FleetMapInfoWindow({
    position,
    onCloseClick,
    children,
}: FleetMapInfoWindowProps): ReactNode {
    return (
        <InfoWindow position={position} onCloseClick={onCloseClick}>
            <div
                className="min-w-[120px] max-w-[280px] rounded border border-border bg-white p-2 text-sm text-[#333333] shadow-sm"
                style={{ color: '#333333' }}
            >
                {children}
            </div>
        </InfoWindow>
    );
}
