import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';

type Row = {
    id: number;
    driver_name: string | null;
    date: string;
    shift_start_time: string | null;
    shift_end_time: string | null;
    driving_time_minutes: number;
    break_time_minutes: number;
    rest_time_minutes: number;
    total_duty_time_minutes: number;
    wtd_compliant: boolean;
    rtd_compliant: boolean;
};

type Props = {
    rows: Row[];
    drivers: { id: number; name: string }[];
    filters: { date_from: string; date_to: string; driver_id?: string };
};

export default function EldReport({ rows, drivers, filters }: Props) {
    const form = useForm({
        date_from: filters.date_from,
        date_to: filters.date_to,
        driver_id: filters.driver_id ?? '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Reports', href: '/fleet/reports' },
        { title: 'ELD / HOS', href: '/fleet/reports/eld' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – ELD / HOS Report" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        ELD / HOS compliance report
                    </h1>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/fleet/reports">Back to reports</Link>
                    </Button>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        router.get(
                            '/fleet/reports/eld',
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
                    <div className="space-y-1">
                        <Label>Driver</Label>
                        <select
                            value={form.data.driver_id}
                            onChange={(e) =>
                                form.setData('driver_id', e.target.value)
                            }
                            className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {drivers.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" size="sm">
                        Apply
                    </Button>
                </form>
                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full min-w-[800px] text-sm">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="p-3 text-left font-medium">
                                    Driver
                                </th>
                                <th className="p-3 text-left font-medium">
                                    Date
                                </th>
                                <th className="p-3 text-left font-medium">
                                    Shift
                                </th>
                                <th className="p-3 text-right font-medium">
                                    Driving (min)
                                </th>
                                <th className="p-3 text-right font-medium">
                                    Break (min)
                                </th>
                                <th className="p-3 text-right font-medium">
                                    Rest (min)
                                </th>
                                <th className="p-3 text-right font-medium">
                                    Duty (min)
                                </th>
                                <th className="p-3 text-center font-medium">
                                    WTD
                                </th>
                                <th className="p-3 text-center font-medium">
                                    RTD
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={9}
                                        className="p-8 text-center text-muted-foreground"
                                    >
                                        No records for this period.
                                    </td>
                                </tr>
                            ) : (
                                rows.map((row) => (
                                    <tr
                                        key={row.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="p-3">
                                            {row.driver_name ?? '-'}
                                        </td>
                                        <td className="p-3">{row.date}</td>
                                        <td className="p-3">
                                            {row.shift_start_time ?? '-'} –{' '}
                                            {row.shift_end_time ?? '-'}
                                        </td>
                                        <td className="p-3 text-right">
                                            {row.driving_time_minutes}
                                        </td>
                                        <td className="p-3 text-right">
                                            {row.break_time_minutes}
                                        </td>
                                        <td className="p-3 text-right">
                                            {row.rest_time_minutes}
                                        </td>
                                        <td className="p-3 text-right">
                                            {row.total_duty_time_minutes}
                                        </td>
                                        <td className="p-3 text-center">
                                            {row.wtd_compliant ? 'Yes' : 'No'}
                                        </td>
                                        <td className="p-3 text-center">
                                            {row.rtd_compliant ? 'Yes' : 'No'}
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
