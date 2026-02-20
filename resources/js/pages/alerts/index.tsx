import Heading from '@/components/heading';
import { RrmcsGuidance } from '@/components/rrmcs-guidance';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Rake {
    id: number;
    rake_number: string;
}

interface AlertItem {
    id: number;
    type: string;
    title: string;
    body: string | null;
    severity: string;
    status: string;
    created_at: string;
    resolved_at: string | null;
    rake_id: number | null;
    siding_id: number | null;
    rake?: Rake | null;
    siding?: Siding | null;
}

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    alerts: {
        data: AlertItem[];
        current_page: number;
        last_page: number;
        links: PaginatorLink[];
    };
    sidings: Siding[];
}

export default function AlertsIndex({ alerts, sidings }: Props) {
    const { url } = usePage();
    const q = new URLSearchParams(url.split('?')[1] || '');
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Alerts', href: '/alerts' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Alerts" />
            <div className="space-y-6">
                <Heading
                    title="Alerts"
                    description="Demurrage, overload, and RR mismatch alerts"
                />
                <RrmcsGuidance
                    title="What this section is for"
                    before="No formal alert system; demurrage discovered only when RR arrived (24+ hours late)."
                    after="Automated alerts at 60/30/0 minutes remaining; escalation by role (operator → in-charge → management)."
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Alert list</CardTitle>
                        <CardDescription>
                            Filter by siding or status
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            method="get"
                            className="mb-6 flex flex-wrap items-end gap-3"
                            onSubmit={(e) => {
                                e.preventDefault();
                                const form = e.currentTarget;
                                const siding = (
                                    form.querySelector(
                                        '[name=siding_id]',
                                    ) as HTMLSelectElement
                                )?.value;
                                const status = (
                                    form.querySelector(
                                        '[name=status]',
                                    ) as HTMLSelectElement
                                )?.value;
                                const params = new URLSearchParams();
                                if (siding) params.set('siding_id', siding);
                                if (status) params.set('status', status);
                                router.get(
                                    '/alerts',
                                    Object.fromEntries(params),
                                );
                            }}
                        >
                            <div className="grid gap-2">
                                <label className="text-sm font-medium">
                                    Siding
                                </label>
                                <select
                                    name="siding_id"
                                    defaultValue={q.get('siding_id') ?? ''}
                                    className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                >
                                    <option value="">All</option>
                                    {sidings.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <label className="text-sm font-medium">
                                    Status
                                </label>
                                <select
                                    name="status"
                                    defaultValue={q.get('status') ?? ''}
                                    className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                >
                                    <option value="">All</option>
                                    <option value="active">Active</option>
                                    <option value="resolved">Resolved</option>
                                </select>
                            </div>
                            <Button type="submit" variant="secondary">
                                Filter
                            </Button>
                        </form>
                        {alerts.data.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                <AlertTriangle className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <p>No alerts found.</p>
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Type
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Title
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Rake / Siding
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Severity
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Status
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Created
                                                </th>
                                                <th className="px-5 py-3.5 text-right font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {alerts.data.map((alert) => (
                                                <tr
                                                    key={alert.id}
                                                    className="border-b last:border-0 hover:bg-muted/30"
                                                >
                                                    <td className="px-5 py-3.5">
                                                        {alert.type}
                                                    </td>
                                                    <td className="px-5 py-3.5 font-medium">
                                                        {alert.title}
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        {alert.rake
                                                            ? alert.rake
                                                                  .rake_number
                                                            : alert.siding
                                                              ? alert.siding
                                                                    .name
                                                              : '—'}
                                                    </td>
                                                    <td className="px-5 py-3.5 capitalize">
                                                        {alert.severity}
                                                    </td>
                                                    <td className="px-5 py-3.5 capitalize">
                                                        {alert.status}
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        {alert.created_at}
                                                    </td>
                                                    <td className="px-5 py-3.5 text-right">
                                                        {alert.status ===
                                                            'active' && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    router.put(
                                                                        `/alerts/${alert.id}/resolve`,
                                                                        {
                                                                            redirect:
                                                                                '/alerts',
                                                                        },
                                                                    )
                                                                }
                                                            >
                                                                Resolve
                                                            </Button>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {alerts.last_page > 1 && (
                                    <nav className="mt-6 flex flex-wrap items-center justify-center gap-4 pt-2">
                                        {alerts.links.map((link) => (
                                            <button
                                                key={link.label}
                                                type="button"
                                                disabled={!link.url}
                                                className="rounded-md border border-input px-4 py-2.5 text-sm disabled:opacity-50"
                                                onClick={() =>
                                                    link.url &&
                                                    router.get(link.url)
                                                }
                                            >
                                                {link.label}
                                            </button>
                                        ))}
                                    </nav>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
