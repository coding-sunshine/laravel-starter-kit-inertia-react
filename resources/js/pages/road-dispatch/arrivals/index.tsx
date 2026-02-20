import { GlossaryTerm } from '@/components/glossary-term';
import Heading from '@/components/heading';
import { RrmcsGuidance } from '@/components/rrmcs-guidance';
import { StatusPill } from '@/components/status-pill';
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
import { Head, Link, router } from '@inertiajs/react';
import { Truck } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Vehicle {
    id: number;
    vehicle_number: string;
    owner_name: string | null;
}

interface Arrival {
    id: number;
    siding_id: number;
    vehicle_id: number;
    status: string;
    arrived_at: string;
    gross_weight: string | null;
    tare_weight: string | null;
    net_weight: string | null;
    unloaded_quantity: string | null;
    siding?: Siding;
    vehicle?: Vehicle;
}

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    arrivals: {
        data: Arrival[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        links: PaginatorLink[];
    };
    sidings: Siding[];
}

export default function RoadDispatchArrivalsIndex({
    arrivals,
    sidings,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Road Dispatch', href: '/road-dispatch/arrivals' },
        { title: 'Vehicle Arrivals', href: '/road-dispatch/arrivals' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vehicle Arrivals" />
            <div className="space-y-6">
                <Heading
                    title="Vehicle Arrivals"
                    description="Record and view vehicle arrivals at siding"
                />
                <div className="flex flex-wrap items-center gap-2">
                    <Link href="/road-dispatch/arrivals/create">
                        <Button>
                            <Truck className="mr-2 size-4" />
                            Record arrival
                        </Button>
                    </Link>
                </div>
                <RrmcsGuidance
                    title="What this section is for"
                    before="Vehicle arrivals logged in a paper register at the gate, with manual tonnage tallying."
                    after="Digital arrival log with vehicle/driver details, linked to stock movement — no paper register needed."
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Arrivals</CardTitle>
                        <CardDescription>
                            Filter by siding and date
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
                                const from = (
                                    form.querySelector(
                                        '[name=date_from]',
                                    ) as HTMLInputElement
                                )?.value;
                                const to = (
                                    form.querySelector(
                                        '[name=date_to]',
                                    ) as HTMLInputElement
                                )?.value;
                                const params = new URLSearchParams();
                                if (siding) params.set('siding_id', siding);
                                if (from) params.set('date_from', from);
                                if (to) params.set('date_to', to);
                                router.get(
                                    '/road-dispatch/arrivals',
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
                                    From
                                </label>
                                <input
                                    type="date"
                                    name="date_from"
                                    className="rounded-md border border-input bg-background px-4 py-2.5 pr-9 text-sm"
                                />
                            </div>
                            <div className="grid gap-2">
                                <label className="text-sm font-medium">
                                    To
                                </label>
                                <input
                                    type="date"
                                    name="date_to"
                                    className="rounded-md border border-input bg-background px-4 py-2.5 pr-9 text-sm"
                                />
                            </div>
                            <Button type="submit" variant="secondary">
                                Filter
                            </Button>
                        </form>
                        {arrivals.data.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                No arrivals found
                            </div>
                        ) : (
                            <div className="mt-4 overflow-x-auto rounded-md border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="px-6 py-4 text-left font-medium">
                                                Arrived at
                                            </th>
                                            <th className="px-6 py-4 text-left font-medium">
                                                Siding
                                            </th>
                                            <th className="px-6 py-4 text-left font-medium">
                                                Vehicle
                                            </th>
                                            <th className="px-6 py-4 text-left font-medium">
                                                Status
                                            </th>
                                            <th className="px-6 py-4 text-right font-medium">
                                                Net (<GlossaryTerm term="MT">MT</GlossaryTerm>)
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {arrivals.data.map((a) => (
                                            <tr
                                                key={a.id}
                                                className="border-b last:border-0 hover:bg-muted/30"
                                            >
                                                <td className="px-6 py-4">
                                                    {new Date(
                                                        a.arrived_at,
                                                    ).toLocaleString()}
                                                </td>
                                                <td className="px-6 py-4">
                                                    {a.siding
                                                        ? `${a.siding.code} (${a.siding.name})`
                                                        : '—'}
                                                </td>
                                                <td className="px-6 py-4">
                                                    {a.vehicle
                                                        ? a.vehicle
                                                              .vehicle_number
                                                        : a.vehicle_id}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <StatusPill
                                                        status={a.status}
                                                    />
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    {a.net_weight ??
                                                        a.unloaded_quantity ??
                                                        '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        {(arrivals.prev_page_url || arrivals.next_page_url) && (
                            <nav className="mt-6 flex items-center justify-center gap-4 pt-2">
                                {arrivals.prev_page_url ? (
                                    <Link
                                        href={arrivals.prev_page_url}
                                        className="text-sm font-medium underline"
                                    >
                                        Previous
                                    </Link>
                                ) : null}
                                <span className="text-sm text-muted-foreground">
                                    Page {arrivals.current_page} of{' '}
                                    {arrivals.last_page}
                                </span>
                                {arrivals.next_page_url ? (
                                    <Link
                                        href={arrivals.next_page_url}
                                        className="text-sm font-medium underline"
                                    >
                                        Next
                                    </Link>
                                ) : null}
                            </nav>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
