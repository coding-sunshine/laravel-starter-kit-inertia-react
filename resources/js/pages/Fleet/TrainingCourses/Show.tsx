import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface TrainingCourse { id: number; course_name: string; course_code?: string; category: string; delivery_method: string; duration_hours: string; }
interface Props { trainingCourse: TrainingCourse; }

export default function FleetTrainingCoursesShow({ trainingCourse }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Training courses', href: '/fleet/training-courses' },
        { title: 'View', href: `/fleet/training-courses/${trainingCourse.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Training course" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{trainingCourse.course_name}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/training-courses/${trainingCourse.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/training-courses">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Category:</span> {trainingCourse.category}</p>
                        <p><span className="font-medium">Delivery:</span> {trainingCourse.delivery_method}</p>
                        <p><span className="font-medium">Duration (hours):</span> {trainingCourse.duration_hours}</p>
                        {trainingCourse.course_code && <p><span className="font-medium">Code:</span> {trainingCourse.course_code}</p>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
