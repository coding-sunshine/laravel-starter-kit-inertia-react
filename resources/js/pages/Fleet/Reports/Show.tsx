import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Report { id: number; name: string; description?: string; report_type: string; format: string; is_active: boolean; }
interface Props { report: Report; }

export default function FleetReportsShow({ report }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Reports', href: '/fleet/reports' },
        { title: 'View', href: `/fleet/reports/${report.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Report" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{report.name}</h1>
                    <div className="flex gap-2">
                        <form action={`/fleet/reports/${report.id}/run`} method="post" className="inline">
                            <Button type="submit" size="sm">Run report</Button>
                        </form>
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/reports/${report.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/reports">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Type:</span> {report.report_type}</p>
                        <p><span className="font-medium">Format:</span> {report.format}</p>
                        <p><span className="font-medium">Active:</span> {report.is_active ? 'Yes' : 'No'}</p>
                        {report.description && <p className="mt-2"><span className="font-medium">Description:</span> {report.description}</p>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
