import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Alert {
    id: number;
    title: string;
    description: string;
    alert_type: string;
    severity: string;
    status: string;
    triggered_at: string;
    acknowledged_at?: string;
}
interface Props {
    alert: Alert;
}

export default function FleetAlertsShow({ alert: alertRow }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Alerts', href: '/fleet/alerts' },
        { title: 'View', href: `/fleet/alerts/${alertRow.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Alert" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{alertRow.title}</h1>
                    <div className="flex gap-2">
                        {alertRow.status === 'active' && (
                            <Form
                                action={`/fleet/alerts/${alertRow.id}/acknowledge`}
                                method="post"
                                className="inline"
                            >
                                <Button type="submit" size="sm">
                                    Acknowledge
                                </Button>
                            </Form>
                        )}
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/alerts">Back to list</Link>
                        </Button>
                    </div>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Type:</span>{' '}
                            {alertRow.alert_type}
                        </p>
                        <p>
                            <span className="font-medium">Severity:</span>{' '}
                            {alertRow.severity}
                        </p>
                        <p>
                            <span className="font-medium">Status:</span>{' '}
                            {alertRow.status}
                        </p>
                        <p>
                            <span className="font-medium">Triggered:</span>{' '}
                            {new Date(alertRow.triggered_at).toLocaleString()}
                        </p>
                        {alertRow.acknowledged_at && (
                            <p>
                                <span className="font-medium">
                                    Acknowledged:
                                </span>{' '}
                                {new Date(
                                    alertRow.acknowledged_at,
                                ).toLocaleString()}
                            </p>
                        )}
                        <p className="mt-2">
                            <span className="font-medium">Description:</span>
                        </p>
                        <p className="text-muted-foreground">
                            {alertRow.description}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
