import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    contractors: { id: number; name: string }[];
    complianceTypes: { value: string; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetContractorComplianceCreate({ contractors, complianceTypes, statuses }: Props) {
    const form = useForm({
        contractor_id: '' as number | '',
        compliance_type: '' as string,
        status: 'valid',
        reference_number: '',
        issue_date: '',
        expiry_date: '',
        document_url: '',
        notes: '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Contractor compliance', href: '/fleet/contractor-compliance' },
        { title: 'New', href: '/fleet/contractor-compliance/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New contractor compliance" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href="/fleet/contractor-compliance">Back</Link></Button>
                    <h1 className="text-2xl font-semibold">New contractor compliance</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.post('/fleet/contractor-compliance'); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Contractor</Label>
                        <select required value={form.data.contractor_id} onChange={e => form.setData('contractor_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {contractors.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Compliance type</Label>
                        <select required value={form.data.compliance_type} onChange={e => form.setData('compliance_type', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {complianceTypes.map((c) => <option key={c.value} value={c.value}>{c.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Status</Label>
                        <select value={form.data.status} onChange={e => form.setData('status', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Reference number</Label>
                        <Input value={form.data.reference_number} onChange={e => form.setData('reference_number', e.target.value)} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Issue date</Label>
                            <Input type="date" value={form.data.issue_date} onChange={e => form.setData('issue_date', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Expiry date</Label>
                            <Input type="date" value={form.data.expiry_date} onChange={e => form.setData('expiry_date', e.target.value)} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Document URL</Label>
                        <Input value={form.data.document_url} onChange={e => form.setData('document_url', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label>Notes</Label>
                        <textarea value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} className="min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm" />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href="/fleet/contractor-compliance">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
