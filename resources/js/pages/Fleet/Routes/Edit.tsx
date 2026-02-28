import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Option { value: string; name: string; }
interface LocationOption { id: number; name: string; }
interface RouteRecord {
    id: number;
    name: string;
    route_type: string;
    description: string | null;
    start_location_id: number | null;
    end_location_id: number | null;
    estimated_distance_km: number | null;
    estimated_duration_minutes: number | null;
    is_active: boolean;
}
interface Props { route: RouteRecord; routeTypes: Option[]; locations: LocationOption[]; }

export default function FleetRoutesEdit({ route, routeTypes, locations }: Props) {
    const form = useForm({
        name: route.name,
        route_type: route.route_type,
        description: route.description ?? '',
        start_location_id: (route.start_location_id ?? '') as number | '',
        end_location_id: (route.end_location_id ?? '') as number | '',
        estimated_distance_km: (route.estimated_distance_km ?? '') as number | '',
        estimated_duration_minutes: (route.estimated_duration_minutes ?? '') as number | '',
        is_active: route.is_active,
    });
    const { data, setData, put, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/routes' }, { title: 'Routes', href: '/fleet/routes' },
        { title: route.name, href: `/fleet/routes/${route.id}` }, { title: 'Edit', href: `/fleet/routes/${route.id}/edit` },
    ];
    const transform = (d: typeof data) => ({
        ...d,
        start_location_id: d.start_location_id === '' ? null : Number(d.start_location_id),
        end_location_id: d.end_location_id === '' ? null : Number(d.end_location_id),
        estimated_distance_km: d.estimated_distance_km === '' ? null : Number(d.estimated_distance_km),
        estimated_duration_minutes: d.estimated_duration_minutes === '' ? null : Number(d.estimated_duration_minutes),
    });
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${route.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit route</h1>
                <form onSubmit={(e) => { e.preventDefault(); form.transform(transform); form.put(`/fleet/routes/${route.id}`); }} className="max-w-xl space-y-4">
                    <div><Label htmlFor="name">Name *</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1" />{errors.name && <p className="mt-1 text-sm text-destructive">{errors.name}</p>}</div>
                    <div><Label htmlFor="route_type">Type *</Label><select id="route_type" value={data.route_type} onChange={(e) => setData('route_type', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm">{routeTypes.map((o) => (<option key={o.value} value={o.value}>{o.name}</option>))}</select>{errors.route_type && <p className="mt-1 text-sm text-destructive">{errors.route_type}</p>}</div>
                    <div><Label htmlFor="description">Description</Label><textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={2} className="mt-1 flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm" />{errors.description && <p className="mt-1 text-sm text-destructive">{errors.description}</p>}</div>
                    <div className="grid grid-cols-2 gap-4">
                        <div><Label htmlFor="start_location_id">Start location</Label><select id="start_location_id" value={data.start_location_id === '' ? '' : String(data.start_location_id)} onChange={(e) => setData('start_location_id', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"><option value="">None</option>{locations.map((l) => (<option key={l.id} value={l.id}>{l.name}</option>))}</select>{errors.start_location_id && <p className="mt-1 text-sm text-destructive">{errors.start_location_id}</p>}</div>
                        <div><Label htmlFor="end_location_id">End location</Label><select id="end_location_id" value={data.end_location_id === '' ? '' : String(data.end_location_id)} onChange={(e) => setData('end_location_id', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"><option value="">None</option>{locations.map((l) => (<option key={l.id} value={l.id}>{l.name}</option>))}</select>{errors.end_location_id && <p className="mt-1 text-sm text-destructive">{errors.end_location_id}</p>}</div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div><Label htmlFor="estimated_distance_km">Estimated distance (km)</Label><Input id="estimated_distance_km" type="number" min={0} step="0.01" value={data.estimated_distance_km === '' ? '' : String(data.estimated_distance_km)} onChange={(e) => setData('estimated_distance_km', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1" />{errors.estimated_distance_km && <p className="mt-1 text-sm text-destructive">{errors.estimated_distance_km}</p>}</div>
                        <div><Label htmlFor="estimated_duration_minutes">Estimated duration (min)</Label><Input id="estimated_duration_minutes" type="number" min={0} value={data.estimated_duration_minutes === '' ? '' : String(data.estimated_duration_minutes)} onChange={(e) => setData('estimated_duration_minutes', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1" />{errors.estimated_duration_minutes && <p className="mt-1 text-sm text-destructive">{errors.estimated_duration_minutes}</p>}</div>
                    </div>
                    <div className="flex items-center gap-2"><input type="checkbox" id="is_active" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="h-4 w-4 rounded border-input" /><Label htmlFor="is_active">Active</Label></div>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Update route</Button><Button variant="outline" asChild><Link href={`/fleet/routes/${route.id}`}>Cancel</Link></Button></div>
                </form>
            </div>
        </AppLayout>
    );
}
