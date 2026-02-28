import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface EvChargingSession { id: number; vehicle_id: number; driver_id?: number; charging_station_id: number; session_id: string; start_timestamp: string; session_type: string; }
interface Props {
    evChargingSession: EvChargingSession;
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; name: string }[];
    evChargingStations: { id: number; name: string }[];
    sessionTypes: { value: string; name: string }[];
}

export default function FleetEvChargingSessionsEdit({ evChargingSession, vehicles, drivers, evChargingStations, sessionTypes }: Props) {
    const form = useForm({
        vehicle_id: evChargingSession.vehicle_id,
        driver_id: evChargingSession.driver_id ?? ('' as number | ''),
        charging_station_id: evChargingSession.charging_station_id,
        session_id: evChargingSession.session_id,
        start_timestamp: evChargingSession.start_timestamp?.slice(0, 16) ?? '',
        session_type: evChargingSession.session_type,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'EV charging sessions', href: '/fleet/ev-charging-sessions' },
        { title: 'Edit', href: `/fleet/ev-charging-sessions/${evChargingSession.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit EV charging session" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href={`/fleet/ev-charging-sessions/${evChargingSession.id}`}>Back</Link></Button>
                    <h1 className="text-2xl font-semibold">Edit EV charging session</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.put(`/fleet/ev-charging-sessions/${evChargingSession.id}`); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Vehicle</Label>
                        <select required value={form.data.vehicle_id} onChange={e => form.setData('vehicle_id', Number(e.target.value))} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {vehicles.map((v) => <option key={v.id} value={v.id}>{v.registration}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Charging station</Label>
                        <select required value={form.data.charging_station_id} onChange={e => form.setData('charging_station_id', Number(e.target.value))} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {evChargingStations.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Session ID</Label>
                        <Input value={form.data.session_id} onChange={e => form.setData('session_id', e.target.value)} required />
                    </div>
                    <div className="space-y-2">
                        <Label>Start</Label>
                        <Input type="datetime-local" value={form.data.start_timestamp} onChange={e => form.setData('start_timestamp', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label>Session type</Label>
                        <select value={form.data.session_type} onChange={e => form.setData('session_type', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {sessionTypes.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Driver</Label>
                        <select value={form.data.driver_id} onChange={e => form.setData('driver_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {drivers.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Update</Button>
                        <Button type="button" variant="outline" asChild><Link href={`/fleet/ev-charging-sessions/${evChargingSession.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
