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

interface Unload {
    id: number;
    siding_id: number;
    vehicle_id: number;
    state: string;
    arrival_time: string;
    unload_end_time: string | null;
    mine_weight_mt: string | null;
    weighment_weight_mt: string | null;
    variance_mt: string | null;
    siding?: Siding;
    vehicle?: Vehicle;
}

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    unloads: {
        data: Unload[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        links: PaginatorLink[];
    };
    sidings: Siding[];
}

export default function RoadDispatchUnloadsIndex({ unloads, sidings }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Road Dispatch', href: '/road-dispatch/unloads' },
        { title: 'Vehicle Unloads', href: '/road-dispatch/unloads' },
    ];

    function handleConfirm(id: number) {
        if (!confirm('Confirm receipt and update stock?')) return;
        router.put(`/road-dispatch/unloads/${id}/confirm`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vehicle Unloads" />
            <div className="space-y-6">
                <Heading
                    title="Vehicle Unloads (Receipt confirmation)"
                    description="Record and confirm vehicle unloads; confirm to update siding stock"
                />
                <div className="flex flex-wrap items-center gap-2">
                    <Link href="/road-dispatch/unloads/create">
                        <Button>
                            <Truck className="mr-2 size-4" />
                            Record unload
                        </Button>
                    </Link>
                </div>
                <RrmcsGuidance
                    title="What this section is for"
                    before="Unload confirmation done by phone call or physical challan, often delayed."
                    after="Real-time unload confirmation with weight capture, instant stock update."
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Unloads</CardTitle>
                        <CardDescription>
                            Filter by siding and state
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
                                const state = (
                                    form.querySelector(
                                        '[name=state]',
                                    ) as HTMLSelectElement
                                )?.value;
                                const params = new URLSearchParams();
                                if (siding) params.set('siding_id', siding);
                                if (state) params.set('state', state);
                                router.get(
                                    '/road-dispatch/unloads',
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
                                    State
                                </label>
                                <select
                                    name="state"
                                    className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                >
                                    <option value="">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="unloading">Unloading</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <Button type="submit" variant="secondary">
                                Filter
                            </Button>
                        </form>
                        {unloads.data.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                No unloads found
                            </div>
                        ) : (
                            <div className="overflow-x-auto rounded-md border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="px-5 py-3.5 text-left font-medium">
                                                Arrival time
                                            </th>
                                            <th className="px-5 py-3.5 text-left font-medium">
                                                Siding
                                            </th>
                                            <th className="px-5 py-3.5 text-left font-medium">
                                                Vehicle
                                            </th>
                                            <th className="px-5 py-3.5 text-left font-medium">
                                                State
                                            </th>
                                            <th className="px-5 py-3.5 text-right font-medium">
                                                Mine (MT)
                                            </th>
                                            <th className="px-5 py-3.5 text-right font-medium">
                                                <GlossaryTerm term="Weighment">
                                                    Weighment
                                                </GlossaryTerm>{' '}
                                                (
                                                <GlossaryTerm term="MT">
                                                    MT
                                                </GlossaryTerm>
                                                )
                                            </th>
                                            <th className="px-5 py-3.5 text-right font-medium">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {unloads.data.map((u) => (
                                            <tr
                                                key={u.id}
                                                className="border-b last:border-0 hover:bg-muted/30"
                                            >
                                                <td className="px-5 py-3.5">
                                                    {new Date(
                                                        u.arrival_time,
                                                    ).toLocaleString()}
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    {u.siding
                                                        ? `${u.siding.code} (${u.siding.name})`
                                                        : '—'}
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    {u.vehicle
                                                        ? u.vehicle
                                                              .vehicle_number
                                                        : u.vehicle_id}
                                                </td>
                                                <td className="px-5 py-3.5">
                                                    <StatusPill
                                                        status={u.state}
                                                    />
                                                </td>
                                                <td className="px-5 py-3.5 text-right">
                                                    {u.mine_weight_mt ?? '—'}
                                                </td>
                                                <td className="px-5 py-3.5 text-right">
                                                    {u.weighment_weight_mt ??
                                                        '—'}
                                                </td>
                                                <td className="px-5 py-3.5 text-right">
                                                    <div className="flex flex-wrap items-center justify-end gap-2">
                                                        <Link
                                                            href={`/road-dispatch/unloads/${u.id}`}
                                                        >
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="outline"
                                                            >
                                                                View
                                                            </Button>
                                                        </Link>
                                                        {(u.state === 'pending' ||
                                                            u.state === 'unloading') && (
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="secondary"
                                                                onClick={() =>
                                                                    handleConfirm(
                                                                        u.id,
                                                                    )
                                                                }
                                                            >
                                                                Confirm receipt
                                                            </Button>
                                                        )}
                                                        {u.unload_end_time && (
                                                            <span className="text-sm text-muted-foreground">
                                                                {new Date(
                                                                    u.unload_end_time,
                                                                ).toLocaleString()}
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        {(unloads.prev_page_url || unloads.next_page_url) && (
                            <nav className="mt-6 flex items-center justify-center gap-4 pt-2">
                                {unloads.prev_page_url ? (
                                    <Link
                                        href={unloads.prev_page_url}
                                        className="text-sm font-medium underline"
                                    >
                                        Previous
                                    </Link>
                                ) : null}
                                <span className="text-sm text-muted-foreground">
                                    Page {unloads.current_page} of{' '}
                                    {unloads.last_page}
                                </span>
                                {unloads.next_page_url ? (
                                    <Link
                                        href={unloads.next_page_url}
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
