'use client';

import { Clusterer } from '@react-google-maps/marker-clusterer';
import { useEffect, useRef } from 'react';

export interface MapVehicleRow {
    id: number;
    lat: number;
    lng: number;
    registration: string;
    source: 'current' | 'home';
}

interface FleetMapClustererProps {
    map: google.maps.Map | null;
    vehicles: MapVehicleRow[];
    onSelectVehicle: (id: number) => void;
}

export function FleetMapClusterer({
    map,
    vehicles,
    onSelectVehicle,
}: FleetMapClustererProps): null {
    const clustererRef = useRef<InstanceType<typeof Clusterer> | null>(null);
    const markersRef = useRef<google.maps.Marker[]>([]);

    useEffect(() => {
        if (!map || vehicles.length === 0) {
            return;
        }

        const markers: google.maps.Marker[] = vehicles.map((v) => {
            const marker = new google.maps.Marker({
                position: { lat: v.lat, lng: v.lng },
                title: `${v.registration}${v.source === 'home' ? ' (home)' : ''}`,
                label: {
                    text: v.registration.slice(0, 2).toUpperCase(),
                    color: '#333333',
                    fontWeight: '600',
                },
            });
            marker.addListener('click', () => onSelectVehicle(v.id));
            return marker;
        });

        markersRef.current = markers;
        const clusterer = new Clusterer(map, markers, {
            gridSize: 60,
            maxZoom: 18,
            minimumClusterSize: 2,
        });
        clustererRef.current = clusterer;

        return () => {
            clusterer.clearMarkers();
            markersRef.current = [];
            clustererRef.current = null;
        };
    }, [map, vehicles, onSelectVehicle]);

    return null;
}
