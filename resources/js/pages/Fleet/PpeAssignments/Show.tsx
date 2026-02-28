import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Assignment {
    id: number;
    ppe_type: string;
    item_reference?: string;
    issued_date: string;
    expiry_or_return_date?: string;
    status: string;
    user?: { id: number; name: string };
    driver?: { id: number; first_name: string; last_name: string };
}
interface Props { ppeAssignment: Assignment; }

export default function PpeAssignmentsShow({ ppeAssignment }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'PPE assignments', href: '/fleet/ppe-assignments' },
        { title: ppeAssignment.ppe_type, href: `/fleet/ppe-assignments/${ppeAssignment.id}` },
    ];
    const assignedTo = ppeAssignment.user?.name ?? (ppeAssignment.driver ? ppeAssignment.driver.first_name + ' ' + ppeAssignment.driver.last_name : '—');
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – PPE assignment" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{ppeAssignment.ppe_type}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/ppe-assignments/${ppeAssignment.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/ppe-assignments">Back</Link></Button>
                    </div>
                </div>
                <dl className="max-w-md space-y-2 rounded-lg border p-6">
                    <div><dt className="text-sm text-muted-foreground">PPE type</dt><dd className="font-medium">{ppeAssignment.ppe_type}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Assigned to</dt><dd>{assignedTo}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Item reference</dt><dd>{ppeAssignment.item_reference ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Issued date</dt><dd>{ppeAssignment.issued_date}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Expiry / return date</dt><dd>{ppeAssignment.expiry_or_return_date ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Status</dt><dd>{ppeAssignment.status}</dd></div>
                </dl>
            </div>
        </AppLayout>
    );
}
