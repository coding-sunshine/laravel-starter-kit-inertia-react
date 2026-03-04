import { FleetIndexSummaryBar } from '@/components/fleet';
import type { SummaryStat } from '@/components/fleet';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { AlertTriangle, Car, Fuel, Pencil, Plus, PoundSterling, Trash2 } from 'lucide-react';

interface TxRecord {
    id: number;
    transaction_timestamp: string;
    fuel_type: string;
    total_cost: number;
    vehicle?: { id: number; registration: string };
    fuel_card?: { id: number; card_number: string };
}
interface Props {
    fuelTransactions: {
        data: TxRecord[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Record<string, string>;
    vehicles: { id: number; registration: string }[];
    fuelCards: { id: number; card_number: string }[];
    summary?: {
        total_spend_30d: number;
        avg_per_vehicle: number;
        flagged: number;
    };
}

export default function FleetFuelTransactionsIndex({
    fuelTransactions,
    filters,
    vehicles,
    fuelCards,
    summary,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/fuel-transactions' },
        { title: 'Fuel transactions', href: '/fleet/fuel-transactions' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Fuel transactions" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Fuel transactions
                    </h1>
                    <Button asChild>
                        <Link href="/fleet/fuel-transactions/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>

                {summary && (
                    <FleetIndexSummaryBar
                        stats={
                            [
                                { label: 'Total Spend (30d)', value: `£${summary.total_spend_30d.toLocaleString()}`, icon: PoundSterling },
                                { label: 'Avg per Vehicle', value: `£${summary.avg_per_vehicle.toLocaleString()}`, icon: Car },
                                { label: 'Flagged', value: summary.flagged, icon: AlertTriangle, variant: summary.flagged > 0 ? 'warning' : 'default' },
                            ] satisfies SummaryStat[]
                        }
                    />
                )}

                <form
                    method="get"
                    className="flex flex-wrap items-end gap-4 rounded-lg border p-4"
                >
                    <div className="space-y-1">
                        <Label>Vehicle</Label>
                        <select
                            name="vehicle_id"
                            defaultValue={filters.vehicle_id ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {vehicles.map((v) => (
                                <option key={v.id} value={v.id}>
                                    {v.registration}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Fuel card</Label>
                        <select
                            name="fuel_card_id"
                            defaultValue={filters.fuel_card_id ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {fuelCards.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.card_number}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">
                        Filter
                    </Button>
                </form>
                {fuelTransactions.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Fuel className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No fuel transactions yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/fuel-transactions/create">
                                Add transaction
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
                                            Date
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Vehicle
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Fuel type
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Total cost
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {fuelTransactions.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                {new Date(
                                                    row.transaction_timestamp,
                                                ).toLocaleString()}
                                            </td>
                                            <td className="p-3">
                                                {row.vehicle?.registration ??
                                                    '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.fuel_type}
                                            </td>
                                            <td className="p-3">
                                                {row.total_cost}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/fuel-transactions/${row.id}`}
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
                                                        href={`/fleet/fuel-transactions/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/fuel-transactions/${row.id}`}
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
                        {fuelTransactions.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {fuelTransactions.links.map((link, i) => (
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
