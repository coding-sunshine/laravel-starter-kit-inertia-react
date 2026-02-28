import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Contractor {
    id: number;
    name: string;
    code?: string;
    contractor_type?: string;
    status?: string;
    contact_name?: string;
    contact_phone?: string;
    contact_email?: string;
    address?: string;
    postcode?: string;
    city?: string;
    tax_number?: string;
    insurance_reference?: string;
    insurance_expiry?: string;
    notes?: string;
    is_active?: boolean;
}
interface Props { contractor: Contractor; }

export default function FleetContractorsShow({ contractor }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Contractors', href: '/fleet/contractors' },
        { title: 'View', href: `/fleet/contractors/${contractor.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Contractor" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{contractor.name}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/contractors/${contractor.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/contractors">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Name:</span> {contractor.name}</p>
                        {contractor.code && <p><span className="font-medium">Code:</span> {contractor.code}</p>}
                        {contractor.contractor_type && <p><span className="font-medium">Type:</span> {contractor.contractor_type}</p>}
                        {contractor.status && <p><span className="font-medium">Status:</span> {contractor.status}</p>}
                        {contractor.contact_name && <p><span className="font-medium">Contact:</span> {contractor.contact_name}</p>}
                        {contractor.contact_email && <p><span className="font-medium">Email:</span> {contractor.contact_email}</p>}
                        {contractor.contact_phone && <p><span className="font-medium">Phone:</span> {contractor.contact_phone}</p>}
                        {contractor.address && <p><span className="font-medium">Address:</span> {contractor.address}</p>}
                        {(contractor.city || contractor.postcode) && <p><span className="font-medium">City / Postcode:</span> {[contractor.city, contractor.postcode].filter(Boolean).join(' ')}</p>}
                        {contractor.tax_number && <p><span className="font-medium">Tax number:</span> {contractor.tax_number}</p>}
                        {contractor.insurance_reference && <p><span className="font-medium">Insurance reference:</span> {contractor.insurance_reference}</p>}
                        {contractor.insurance_expiry && <p><span className="font-medium">Insurance expiry:</span> {new Date(contractor.insurance_expiry).toLocaleDateString()}</p>}
                        {contractor.notes && <p><span className="font-medium">Notes:</span> {contractor.notes}</p>}
                        {contractor.is_active != null && <p><span className="font-medium">Active:</span> {contractor.is_active ? 'Yes' : 'No'}</p>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
