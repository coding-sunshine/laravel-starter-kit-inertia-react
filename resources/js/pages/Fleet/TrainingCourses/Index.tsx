import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { GraduationCap, Pencil, Plus, Trash2 } from 'lucide-react';

interface Row {
    id: number;
    course_name: string;
    course_code?: string;
    category: string;
    delivery_method: string;
    is_active: boolean;
}
interface Props {
    trainingCourses: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Record<string, string>;
    categories: { value: string; name: string }[];
    deliveryMethods: { value: string; name: string }[];
}

export default function FleetTrainingCoursesIndex({
    trainingCourses,
    filters,
    categories,
    deliveryMethods,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Training courses', href: '/fleet/training-courses' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Training courses" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Training courses</h1>
                    <Button asChild>
                        <Link href="/fleet/training-courses/create">
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
                        <Label>Category</Label>
                        <select
                            name="category"
                            defaultValue={filters.category ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {categories.map((c) => (
                                <option key={c.value} value={c.value}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Delivery</Label>
                        <select
                            name="delivery_method"
                            defaultValue={filters.delivery_method ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {deliveryMethods.map((d) => (
                                <option key={d.value} value={d.value}>
                                    {d.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">
                        Filter
                    </Button>
                </form>
                {trainingCourses.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <GraduationCap className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No training courses yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/training-courses/create">
                                Add course
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
                                            Course name
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Code
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Category
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Delivery
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {trainingCourses.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                {row.course_name}
                                            </td>
                                            <td className="p-3">
                                                {row.course_code ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.category}
                                            </td>
                                            <td className="p-3">
                                                {row.delivery_method}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/training-courses/${row.id}`}
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
                                                        href={`/fleet/training-courses/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/training-courses/${row.id}`}
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
                        {trainingCourses.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {trainingCourses.links.map((link, i) => (
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
