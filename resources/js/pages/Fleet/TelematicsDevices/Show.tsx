import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface DeviceRecord {
    id: number;
    device_id: string;
    provider: string;
    status: string;
    installed_at: string | null;
    last_sync_at: string | null;
    is_active: boolean;
    vehicle?: { id: number; registration: string };
}
interface Props { telematicsDevice: DeviceRecord; }

export default function FleetTelematicsDevicesShow({ telematicsDevice }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/telematics-devices' },
        { title: 'Telematics devices', href: '/fleet/telematics-devices' },
        { title: telematicsDevice.device_id, href: `/fleet/telematics-devices/${telematicsDevice.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${telematicsDevice.device_id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">{telematicsDevice.device_id}</h1>
                        <p className="text-muted-foreground">
                            {telematicsDevice.provider} · {telematicsDevice.status}
                            {telematicsDevice.vehicle && <> · <Link href={`/fleet/vehicles/${telematicsDevice.vehicle.id}`} className="underline">{telematicsDevice.vehicle.registration}</Link></>}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={`/fleet/telematics-devices/${telematicsDevice.id}/edit`}>Edit</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/telematics-devices">Back to devices</Link>
                        </Button>
                    </div>
                </div>
                <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-base">Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Status:</span> {telematicsDevice.status}</p>
                        <p><span className="font-medium">Active:</span> {telematicsDevice.is_active ? 'Yes' : 'No'}</p>
                        <p><span className="font-medium">Installed at:</span> {telematicsDevice.installed_at ? new Date(telematicsDevice.installed_at).toLocaleString() : '—'}</p>
                        <p><span className="font-medium">Last sync:</span> {telematicsDevice.last_sync_at ? new Date(telematicsDevice.last_sync_at).toLocaleString() : '—'}</p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
