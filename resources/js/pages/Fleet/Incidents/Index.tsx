import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { AlertTriangle, Pencil, Plus, Trash2 } from 'lucide-react';

interface IncidentRecord {
    id: number;
    incident_number: string;
    incident_date?: string;
    incident_timestamp?: string;
    incident_type: string;
    severity: string;
    status: string;
    vehicle?: { id: number; registration: string };
}
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
interface Props {
    incidents: { data: IncidentRecord[]; links: PaginationLink[] };
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; first_name: string; last_name: string }[];
    incidentTypes: { value: string; name: string }[];
    severities: { value: string; name: string }[];
    statuses: { value: string; name: string }[];
    faultDeterminations: { value: string; name: string }[];
}

export default function FleetIncidentsIndex({
    incidents,
    vehicles: _vehicles,
    drivers: _drivers,
    incidentTypes: _incidentTypes,
    severities: _severities,
    statuses: _statuses,
    faultDeterminations: _faultDeterminations,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/incidents' },
        { title: 'Incidents', href: '/fleet/incidents' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Incidents" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Incidents</h1>
                    <Button asChild>
                        <Link href="/fleet/incidents/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>
                {incidents.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <AlertTriangle className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No incidents yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/incidents/create">
                                Report incident
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">
                                            Number
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Date
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Type
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Severity
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Status
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Vehicle
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {incidents.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                <Link
                                                    href={`/fleet/incidents/${row.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {row.incident_number}
                                                </Link>
                                            </td>
                                            <td className="p-3">
                                                {row.incident_timestamp
                                                    ? new Date(
                                                          row.incident_timestamp,
                                                      ).toLocaleDateString()
                                                    : row.incident_date
                                                      ? new Date(
                                                            row.incident_date,
                                                        ).toLocaleDateString()
                                                      : '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.incident_type}
                                            </td>
                                            <td className="p-3">
                                                {row.severity}
                                            </td>
                                            <td className="p-3">
                                                {row.status}
                                            </td>
                                            <td className="p-3">
                                                {row.vehicle?.registration ??
                                                    '—'}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/incidents/${row.id}`}
                                                    >
                                                        View
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/incidents/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/incidents/${row.id}`}
                                                    method="delete"
                                                    className="ml-2 inline"
                                                    onSubmit={(e) => {
                                                        if (!confirm('Delete?'))
                                                            e.preventDefault();
                                                    }}
                                                >
                                                    <Button
                                                        type="submit"
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        <Trash2 className="size-3.5 text-destructive" />
                                                    </Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {incidents.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {incidents.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url ?? '#'}
                                        className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                    >
                                        {link.label}
                                    </Link>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
