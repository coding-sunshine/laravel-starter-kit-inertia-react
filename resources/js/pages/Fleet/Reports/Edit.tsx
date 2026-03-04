import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Report {
    id: number;
    name: string;
    description?: string;
    report_type: string;
    format: string;
    schedule_frequency: string;
    is_active: boolean;
}
interface Props {
    report: Report;
    reportTypes: { value: string; name: string }[];
    scheduleFrequencies: { value: string; name: string }[];
    formats: { value: string; name: string }[];
}

export default function FleetReportsEdit({
    report,
    reportTypes,
    scheduleFrequencies,
    formats,
}: Props) {
    const form = useForm({
        name: report.name,
        description: report.description ?? '',
        report_type: report.report_type,
        format: report.format,
        schedule_frequency: report.schedule_frequency,
        is_active: report.is_active,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Reports', href: '/fleet/reports' },
        { title: 'Edit', href: `/fleet/reports/${report.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit report" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/fleet/reports/${report.id}`}>Back</Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">Edit report</h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put(`/fleet/reports/${report.id}`);
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>Name</Label>
                        <Input
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            required
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Report type</Label>
                            <select
                                value={form.data.report_type}
                                onChange={(e) =>
                                    form.setData('report_type', e.target.value)
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {reportTypes.map((r) => (
                                    <option key={r.value} value={r.value}>
                                        {r.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Format</Label>
                            <select
                                value={form.data.format}
                                onChange={(e) =>
                                    form.setData('format', e.target.value)
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {formats.map((f) => (
                                    <option key={f.value} value={f.value}>
                                        {f.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Schedule frequency</Label>
                        <select
                            value={form.data.schedule_frequency}
                            onChange={(e) =>
                                form.setData(
                                    'schedule_frequency',
                                    e.target.value,
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {scheduleFrequencies.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Update
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href={`/fleet/reports/${report.id}`}>
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
