import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Option {
    value: string;
    name: string;
}
interface DownloadRecord {
    id: number;
    driver_id: number;
    download_date: string;
    status: string;
}
interface Props {
    tachographDownload: DownloadRecord;
    drivers: { id: number; first_name: string; last_name: string }[];
    statuses: Option[];
}

export default function FleetTachographDownloadsEdit({
    tachographDownload,
    drivers,
    statuses,
}: Props) {
    const form = useForm({
        driver_id: tachographDownload.driver_id,
        download_date: tachographDownload.download_date.slice(0, 10),
        status: tachographDownload.status,
    });
    const { data, setData, processing } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/tachograph-downloads' },
        { title: 'Tachograph downloads', href: '/fleet/tachograph-downloads' },
        {
            title: `Download #${tachographDownload.id}`,
            href: `/fleet/tachograph-downloads/${tachographDownload.id}`,
        },
        {
            title: 'Edit',
            href: `/fleet/tachograph-downloads/${tachographDownload.id}/edit`,
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/fleet/tachograph-downloads/${tachographDownload.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Fleet – Edit tachograph download #${tachographDownload.id}`}
            />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">
                    Edit tachograph download
                </h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label>Driver *</Label>
                        <select
                            value={data.driver_id}
                            onChange={(e) =>
                                setData('driver_id', Number(e.target.value))
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                        >
                            {drivers.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.last_name}, {d.first_name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label>Download date *</Label>
                        <Input
                            type="date"
                            value={data.download_date}
                            onChange={(e) =>
                                setData('download_date', e.target.value)
                            }
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label>Status</Label>
                        <select
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                        >
                            {statuses.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Update
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={`/fleet/tachograph-downloads/${tachographDownload.id}`}
                            >
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
