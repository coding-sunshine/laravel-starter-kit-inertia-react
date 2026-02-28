import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface TrainingCourse { id: number; course_name: string; course_code?: string; category: string; delivery_method: string; duration_hours: string; is_active: boolean; }
interface Props {
    trainingCourse: TrainingCourse;
    categories: { value: string; name: string }[];
    deliveryMethods: { value: string; name: string }[];
}

export default function FleetTrainingCoursesEdit({ trainingCourse, categories, deliveryMethods }: Props) {
    const form = useForm({
        course_name: trainingCourse.course_name,
        course_code: trainingCourse.course_code ?? '',
        category: trainingCourse.category,
        delivery_method: trainingCourse.delivery_method,
        duration_hours: String(trainingCourse.duration_hours),
        is_active: trainingCourse.is_active,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Training courses', href: '/fleet/training-courses' },
        { title: 'Edit', href: `/fleet/training-courses/${trainingCourse.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit training course" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href={`/fleet/training-courses/${trainingCourse.id}`}>Back</Link></Button>
                    <h1 className="text-2xl font-semibold">Edit training course</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.put(`/fleet/training-courses/${trainingCourse.id}`); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Course name</Label>
                        <Input value={form.data.course_name} onChange={e => form.setData('course_name', e.target.value)} required />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Category</Label>
                            <select value={form.data.category} onChange={e => form.setData('category', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                {categories.map((c) => <option key={c.value} value={c.value}>{c.name}</option>)}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Delivery method</Label>
                            <select value={form.data.delivery_method} onChange={e => form.setData('delivery_method', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                {deliveryMethods.map((d) => <option key={d.value} value={d.value}>{d.name}</option>)}
                            </select>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Update</Button>
                        <Button type="button" variant="outline" asChild><Link href={`/fleet/training-courses/${trainingCourse.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
