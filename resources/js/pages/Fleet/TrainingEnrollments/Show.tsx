import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface TrainingEnrollment { id: number; enrollment_date: string; enrollment_status: string; pass_fail: string; driver?: { first_name: string; last_name: string }; training_session?: { session_name: string }; }
interface Props { trainingEnrollment: TrainingEnrollment; }

export default function FleetTrainingEnrollmentsShow({ trainingEnrollment }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Training enrollments', href: '/fleet/training-enrollments' },
        { title: 'View', href: `/fleet/training-enrollments/${trainingEnrollment.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Training enrollment" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Training enrollment</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/training-enrollments/${trainingEnrollment.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/training-enrollments">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Session:</span> {trainingEnrollment.training_session?.session_name ?? '—'}</p>
                        <p><span className="font-medium">Driver:</span> {trainingEnrollment.driver ? `${trainingEnrollment.driver.first_name} ${trainingEnrollment.driver.last_name}` : '—'}</p>
                        <p><span className="font-medium">Enrollment date:</span> {new Date(trainingEnrollment.enrollment_date).toLocaleDateString()}</p>
                        <p><span className="font-medium">Status:</span> {trainingEnrollment.enrollment_status}</p>
                        <p><span className="font-medium">Pass/Fail:</span> {trainingEnrollment.pass_fail}</p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
