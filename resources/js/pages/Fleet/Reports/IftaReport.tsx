import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';

interface Row {
    vehicle_id: number;
    vehicle_registration: string;
    vehicle_make_model: string;
    total_litres: number;
    total_cost: number;
    transaction_count: number;
}

interface Props {
    rows: Row[];
    filters: { date_from: string; date_to: string };
}

export default function IftaReport({ rows, filters }: Props) {
    const form = useForm({
        date_from: filters.date_from,
        date_to: filters.date_to,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Reports', href: '/fleet/reports' },
        { title: 'IFTA', href: '/fleet/reports/ifta' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – IFTA Report" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">IFTA fuel report</h1>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/fleet/reports">Back to reports</Link>
                    </Button>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        router.get(
                            '/fleet/reports/ifta',
                            form.data as Record<string, string>,
                            { preserveState: true },
                        );
                    }}
                    className="flex flex-wrap items-end gap-4 rounded-lg border p-4"
                >
                    <div className="space-y-1">
                        <Label>From</Label>
                        <Input
                            type="date"
                            value={form.data.date_from}
                            onChange={(e) =>
                                form.setData('date_from', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-1">
                        <Label>To</Label>
                        <Input
                            type="date"
                            value={form.data.date_to}
                            onChange={(e) =>
                                form.setData('date_to', e.target.value)
                            }
                        />
                    </div>
                    <Button type="submit" size="sm">
                        Apply
                    </Button>
                </form>
                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full min-w-[500px] text-sm">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="p-3 text-left font-medium">
                                    Vehicle
                                </th>
                                <th className="p-3 text-left font-medium">
                                    Make / Model
                                </th>
                                <th className="p-3 text-right font-medium">
                                    Total litres
                                </th>
                                <th className="p-3 text-right font-medium">
                                    Total cost
                                </th>
                                <th className="p-3 text-right font-medium">
                                    Transactions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="p-8 text-center text-muted-foreground"
                                    >
                                        No fuel transactions for this period.
                                    </td>
                                </tr>
                            ) : (
                                rows.map((row) => (
                                    <tr
                                        key={row.vehicle_id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="p-3">
                                            {row.vehicle_registration}
                                        </td>
                                        <td className="p-3">
                                            {row.vehicle_make_model || '-'}
                                        </td>
                                        <td className="p-3 text-right">
                                            {row.total_litres.toFixed(2)}
                                        </td>
                                        <td className="p-3 text-right">
                                            {row.total_cost.toFixed(2)}
                                        </td>
                                        <td className="p-3 text-right">
                                            {row.transaction_count}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
