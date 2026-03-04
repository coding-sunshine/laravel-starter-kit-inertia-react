import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface TrainingSession {
    id: number;
    session_name: string;
    scheduled_date: string;
    start_time: string;
    end_time: string;
    status: string;
    training_course?: { course_name: string };
}
interface Props {
    trainingSession: TrainingSession;
}

export default function FleetTrainingSessionsShow({ trainingSession }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Training sessions', href: '/fleet/training-sessions' },
        {
            title: 'View',
            href: `/fleet/training-sessions/${trainingSession.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Training session" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {trainingSession.session_name}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/training-sessions/${trainingSession.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/training-sessions">
                                Back to list
                            </Link>
                        </Button>
                    </div>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Course:</span>{' '}
                            {trainingSession.training_course?.course_name ??
                                '—'}
                        </p>
                        <p>
                            <span className="font-medium">Date:</span>{' '}
                            {new Date(
                                trainingSession.scheduled_date,
                            ).toLocaleDateString()}
                        </p>
                        <p>
                            <span className="font-medium">Time:</span>{' '}
                            {trainingSession.start_time} –{' '}
                            {trainingSession.end_time}
                        </p>
                        <p>
                            <span className="font-medium">Status:</span>{' '}
                            {trainingSession.status}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
