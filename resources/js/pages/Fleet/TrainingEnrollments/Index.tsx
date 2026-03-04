import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { GraduationCap, Pencil, Plus, Trash2 } from 'lucide-react';

interface Row {
    id: number;
    enrollment_date: string;
    enrollment_status: string;
    pass_fail: string;
    driver?: { first_name: string; last_name: string };
    training_session?: { session_name: string };
}
interface Props {
    trainingEnrollments: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Record<string, string>;
    trainingSessions: { id: number; name: string }[];
    drivers: { id: number; name: string }[];
    enrollmentStatuses: { value: string; name: string }[];
}

export default function FleetTrainingEnrollmentsIndex({
    trainingEnrollments,
    filters,
    trainingSessions,
    drivers,
    enrollmentStatuses,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Training enrollments', href: '/fleet/training-enrollments' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Training enrollments" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Training enrollments
                    </h1>
                    <Button asChild>
                        <Link href="/fleet/training-enrollments/create">
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
                        <Label>Session</Label>
                        <select
                            name="training_session_id"
                            defaultValue={filters.training_session_id ?? ''}
                            className="h-9 w-56 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {trainingSessions.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Driver</Label>
                        <select
                            name="driver_id"
                            defaultValue={filters.driver_id ?? ''}
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
                    <div className="space-y-1">
                        <Label>Status</Label>
                        <select
                            name="enrollment_status"
                            defaultValue={filters.enrollment_status ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {enrollmentStatuses.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">
                        Filter
                    </Button>
                </form>
                {trainingEnrollments.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <GraduationCap className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No training enrollments yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/training-enrollments/create">
                                Add enrollment
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
                                            Session
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Driver
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Date
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Status
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Pass/Fail
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {trainingEnrollments.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                {row.training_session
                                                    ?.session_name ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.driver
                                                    ? `${row.driver.first_name} ${row.driver.last_name}`
                                                    : '—'}
                                            </td>
                                            <td className="p-3">
                                                {new Date(
                                                    row.enrollment_date,
                                                ).toLocaleDateString()}
                                            </td>
                                            <td className="p-3">
                                                {row.enrollment_status}
                                            </td>
                                            <td className="p-3">
                                                {row.pass_fail}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/training-enrollments/${row.id}`}
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
                                                        href={`/fleet/training-enrollments/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/training-enrollments/${row.id}`}
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
                        {trainingEnrollments.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {trainingEnrollments.links.map((link, i) => (
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
