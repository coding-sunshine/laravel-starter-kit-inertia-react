import AppLayout from '@/layouts/app-layout';
import {
    FleetMap,
    FleetMapMarker,
} from '@/components/fleet/FleetMap';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link, router, useForm } from '@inertiajs/react';
import { Sparkles, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

interface LocationOption { id: number; name: string; }
interface StopRecord {
    id: number;
    name: string | null;
    sort_order: number;
    planned_arrival_time: string | null;
    planned_departure_time: string | null;
    location?: { id: number; name: string; lat?: number | null; lng?: number | null };
}
interface RouteRecord {
    id: number;
    name: string;
    route_type: string;
    description: string | null;
    is_active: boolean;
    start_location?: { id: number; name: string };
    end_location?: { id: number; name: string };
    stops?: StopRecord[];
}
interface OptimizationResult {
    suggested_stop_order: number[];
    estimated_total_distance_km: number;
    estimated_total_duration_minutes: number;
    estimated_cost: number;
    estimated_carbon_kg: number;
    summary: string;
}
interface Props {
    route: RouteRecord;
    locations: LocationOption[];
    optimizeUrl: string;
    applyOptimizedOrderUrl: string;
}

export default function FleetRoutesShow({ route, locations, optimizeUrl, applyOptimizedOrderUrl }: Props) {
    const [optimizing, setOptimizing] = useState(false);
    const [optimizationResult, setOptimizationResult] = useState<OptimizationResult | null>(null);
    const [applying, setApplying] = useState(false);
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/routes' },
        { title: 'Routes', href: '/fleet/routes' },
        { title: route.name, href: `/fleet/routes/${route.id}` },
    ];
    const stopForm = useForm({
        location_id: '' as number | '',
        name: '',
        sort_order: route.stops?.length ?? 0,
        planned_arrival_time: '',
        planned_departure_time: '',
        notes: '',
    });
    const stops = route.stops ?? [];
    const stopsWithCoords = useMemo(
        () =>
            stops.filter(
                (s): s is StopRecord & { location: { lat: number | string; lng: number | string } } =>
                    s.location != null &&
                    s.location.lat != null &&
                    s.location.lng != null &&
                    !Number.isNaN(Number(s.location.lat)) &&
                    !Number.isNaN(Number(s.location.lng)),
            ),
        [stops],
    );
    const mapCenter = useMemo(() => {
        if (stopsWithCoords.length === 0) return { lat: 51.5, lng: -0.1 };
        const sum = stopsWithCoords.reduce(
            (a, s) => ({
                lat: a.lat + Number(s.location.lat),
                lng: a.lng + Number(s.location.lng),
            }),
            { lat: 0, lng: 0 },
        );
        return { lat: sum.lat / stopsWithCoords.length, lng: sum.lng / stopsWithCoords.length };
    }, [stopsWithCoords]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${route.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">{route.name}</h1>
                        <p className="text-muted-foreground">
                            {route.route_type} · {route.is_active ? 'Active' : 'Inactive'}
                            {route.start_location && ` · Start: ${route.start_location.name}`}
                            {route.end_location && ` · End: ${route.end_location.name}`}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/routes">Back to routes</Link>
                    </Button>
                </div>
                {route.description && <p className="text-sm text-muted-foreground">{route.description}</p>}

                {stopsWithCoords.length > 0 && (
                    <Card className="overflow-hidden border border-border">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base font-semibold text-foreground">Route map</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <FleetMap
                                center={mapCenter}
                                zoom={stopsWithCoords.length >= 2 ? 10 : 12}
                                mapContainerStyle={{ width: '100%', height: '320px' }}
                                className="rounded-b-lg"
                            >
                                {stopsWithCoords.map((s, i) => (
                                    <FleetMapMarker
                                        key={s.id}
                                        position={{ lat: Number(s.location.lat), lng: Number(s.location.lng) }}
                                        title={s.name ?? s.location.name ?? `Stop ${s.sort_order + 1}`}
                                        label={String(i + 1)}
                                    />
                                ))}
                            </FleetMap>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="flex flex-wrap items-center justify-between gap-2 text-base">
                            Route stops
                            {stops.length >= 2 && (
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    disabled={optimizing}
                                    onClick={async () => {
                                        setOptimizing(true);
                                        setOptimizationResult(null);
                                        try {
                                            const res = await fetch(optimizeUrl, {
                                                method: 'POST',
                                                headers: {
                                                    'Accept': 'application/json',
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                },
                                                credentials: 'include',
                                            });
                                            const data = await res.json().catch(() => ({}));
                                            if (res.ok && data.suggested_stop_order) {
                                                setOptimizationResult(data);
                                            }
                                        } finally {
                                            setOptimizing(false);
                                        }
                                    }}
                                >
                                    <Sparkles className="mr-1.5 size-4" />
                                    {optimizing ? 'Optimizing…' : 'Optimize with AI'}
                                </Button>
                            )}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {optimizationResult && (
                            <div className="rounded-lg border border-primary/30 bg-primary/5 p-3 text-sm">
                                <p className="font-medium">{optimizationResult.summary}</p>
                                <p className="mt-1 text-muted-foreground">
                                    Distance: {optimizationResult.estimated_total_distance_km.toFixed(1)} km · Duration: {optimizationResult.estimated_total_duration_minutes.toFixed(0)} min
                                    {optimizationResult.estimated_carbon_kg > 0 && ` · CO₂: ${optimizationResult.estimated_carbon_kg.toFixed(1)} kg`}
                                </p>
                                <Button
                                    type="button"
                                    size="sm"
                                    className="mt-2"
                                    disabled={applying}
                                    onClick={async () => {
                                        setApplying(true);
                                        try {
                                            const res = await fetch(applyOptimizedOrderUrl, {
                                                method: 'POST',
                                                headers: {
                                                    'Accept': 'application/json',
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                },
                                                credentials: 'include',
                                                body: JSON.stringify({ stop_order: optimizationResult.suggested_stop_order }),
                                            });
                                            if (res.ok) {
                                                setOptimizationResult(null);
                                                router.reload();
                                            }
                                        } finally {
                                            setApplying(false);
                                        }
                                    }}
                                >
                                    {applying ? 'Applying…' : 'Apply suggested order'}
                                </Button>
                            </div>
                        )}
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                stopForm.transform((d) => ({
                                    ...d,
                                    location_id: d.location_id === '' ? null : d.location_id,
                                    planned_arrival_time: d.planned_arrival_time || null,
                                    planned_departure_time: d.planned_departure_time || null,
                                }));
                                stopForm.post(`/fleet/routes/${route.id}/route-stops`);
                            }}
                            className="flex flex-wrap items-end gap-4"
                        >
                            <div className="space-y-2">
                                <Label htmlFor="location_id">Location</Label>
                                <select id="location_id" value={stopForm.data.location_id === '' ? '' : String(stopForm.data.location_id)} onChange={(e) => stopForm.setData('location_id', e.target.value === '' ? '' : Number(e.target.value))} className="h-9 min-w-[12rem] rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                    <option value="">None</option>
                                    {locations.map((l) => (<option key={l.id} value={l.id}>{l.name}</option>))}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" value={stopForm.data.name} onChange={(e) => stopForm.setData('name', e.target.value)} className="h-9 min-w-[10rem]" placeholder="Stop name" />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="sort_order">Order</Label>
                                <Input id="sort_order" type="number" min={0} value={stopForm.data.sort_order} onChange={(e) => stopForm.setData('sort_order', Number(e.target.value))} className="h-9 w-20" />
                            </div>
                            <Button type="submit" disabled={stopForm.processing}>{stopForm.processing ? 'Adding…' : 'Add stop'}</Button>
                        </form>
                        {stops.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No stops yet.</p>
                        ) : (
                            <div className="rounded-md border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="p-3 text-left font-medium">Order</th>
                                            <th className="p-3 text-left font-medium">Name / Location</th>
                                            <th className="p-3 text-left font-medium">Planned arrival</th>
                                            <th className="p-3 text-left font-medium">Planned departure</th>
                                            <th className="p-3 text-right font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {stops.map((s) => (
                                            <tr key={s.id} className="border-b last:border-0">
                                                <td className="p-3">{s.sort_order}</td>
                                                <td className="p-3">{s.name || s.location?.name || '—'}</td>
                                                <td className="p-3">{s.planned_arrival_time ? new Date(s.planned_arrival_time).toLocaleString() : '—'}</td>
                                                <td className="p-3">{s.planned_departure_time ? new Date(s.planned_departure_time).toLocaleString() : '—'}</td>
                                                <td className="p-3 text-right">
                                                    <Form action={`/fleet/routes/${route.id}/route-stops/${s.id}`} method="delete" className="inline" onSubmit={(e) => { if (!confirm('Remove this stop?')) e.preventDefault(); }}>
                                                        <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                    </Form>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
