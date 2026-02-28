import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface ContractorInvoice {
    id: number;
    invoice_number: string;
    invoice_date: string;
    due_date?: string;
    subtotal?: string;
    tax_amount?: string;
    total_amount: string;
    status?: string;
    work_order_reference?: string;
    description?: string;
    paid_date?: string;
    payment_reference?: string;
    contractor?: { id: number; name: string };
}
interface Props { contractorInvoice: ContractorInvoice; }

export default function FleetContractorInvoicesShow({ contractorInvoice }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Contractor invoices', href: '/fleet/contractor-invoices' },
        { title: 'View', href: `/fleet/contractor-invoices/${contractorInvoice.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Contractor invoice" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{contractorInvoice.invoice_number}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/contractor-invoices/${contractorInvoice.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/contractor-invoices">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Invoice number:</span> {contractorInvoice.invoice_number}</p>
                        {contractorInvoice.contractor && <p><span className="font-medium">Contractor:</span> {contractorInvoice.contractor.name}</p>}
                        <p><span className="font-medium">Invoice date:</span> {new Date(contractorInvoice.invoice_date).toLocaleDateString()}</p>
                        {contractorInvoice.due_date && <p><span className="font-medium">Due date:</span> {new Date(contractorInvoice.due_date).toLocaleDateString()}</p>}
                        {contractorInvoice.subtotal != null && <p><span className="font-medium">Subtotal:</span> {contractorInvoice.subtotal}</p>}
                        {contractorInvoice.tax_amount != null && <p><span className="font-medium">Tax amount:</span> {contractorInvoice.tax_amount}</p>}
                        <p><span className="font-medium">Total amount:</span> {contractorInvoice.total_amount}</p>
                        {contractorInvoice.status && <p><span className="font-medium">Status:</span> {contractorInvoice.status}</p>}
                        {contractorInvoice.work_order_reference && <p><span className="font-medium">Work order reference:</span> {contractorInvoice.work_order_reference}</p>}
                        {contractorInvoice.description && <p><span className="font-medium">Description:</span> {contractorInvoice.description}</p>}
                        {contractorInvoice.paid_date && <p><span className="font-medium">Paid date:</span> {new Date(contractorInvoice.paid_date).toLocaleDateString()}</p>}
                        {contractorInvoice.payment_reference && <p><span className="font-medium">Payment reference:</span> {contractorInvoice.payment_reference}</p>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
