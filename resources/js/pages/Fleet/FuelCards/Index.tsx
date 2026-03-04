import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { CreditCard, Pencil, Plus, Trash2 } from 'lucide-react';

interface CardRecord {
    id: number;
    card_number: string;
    provider: string;
    status: string;
    assigned_vehicle?: { id: number; registration: string };
    assigned_driver?: { id: number; first_name: string; last_name: string };
}
interface Props {
    fuelCards: {
        data: CardRecord[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Record<string, string>;
}

export default function FleetFuelCardsIndex({ fuelCards, filters }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/fuel-cards' },
        { title: 'Fuel cards', href: '/fleet/fuel-cards' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Fuel cards" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Fuel cards</h1>
                    <Button asChild>
                        <Link href="/fleet/fuel-cards/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>
                <form
                    method="get"
                    className="flex flex-wrap items-end gap-4 rounded-lg border p-4"
                >
                    <div className="space-y-1">
                        <Label htmlFor="status">Status</Label>
                        <select
                            id="status"
                            name="status"
                            defaultValue={filters.status ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="blocked">Blocked</option>
                            <option value="expired">Expired</option>
                            <option value="lost">Lost</option>
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">
                        Filter
                    </Button>
                </form>
                {fuelCards.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <CreditCard className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No fuel cards yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/fuel-cards/create">
                                Add fuel card
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
                                            Card number
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Provider
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Status
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Vehicle
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Driver
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {fuelCards.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                <Link
                                                    href={`/fleet/fuel-cards/${row.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {row.card_number}
                                                </Link>
                                            </td>
                                            <td className="p-3">
                                                {row.provider}
                                            </td>
                                            <td className="p-3">
                                                {row.status}
                                            </td>
                                            <td className="p-3">
                                                {row.assigned_vehicle
                                                    ?.registration ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.assigned_driver
                                                    ? `${row.assigned_driver.first_name} ${row.assigned_driver.last_name}`
                                                    : '—'}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/fuel-cards/${row.id}/edit`}
                                                    >
                                                        <Pencil className="mr-1 size-3.5" />
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/fuel-cards/${row.id}`}
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
                        {fuelCards.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {fuelCards.links.map((link, i) => (
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
