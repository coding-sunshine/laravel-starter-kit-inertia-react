import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Option { value: string; name: string; }
interface ItemRecord { id: number; entity_type: string; entity_id: number; compliance_type: string; title: string; expiry_date: string; status: string; }
interface Props {
    complianceItem: ItemRecord;
    entityTypes: Option[];
    statuses: Option[];
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; first_name: string; last_name: string }[];
}

export default function FleetComplianceItemsEdit({ complianceItem, entityTypes, statuses }: Props) {
    const form = useForm({
        entity_type: complianceItem.entity_type,
        entity_id: complianceItem.entity_id,
        compliance_type: complianceItem.compliance_type,
        title: complianceItem.title,
        expiry_date: complianceItem.expiry_date.slice(0, 10),
        status: complianceItem.status,
    });
    const { data, setData, processing } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/compliance-items' },
        { title: 'Compliance items', href: '/fleet/compliance-items' },
        { title: complianceItem.title, href: `/fleet/compliance-items/${complianceItem.id}` },
        { title: 'Edit', href: `/fleet/compliance-items/${complianceItem.id}/edit` },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({ ...d, entity_id: Number(d.entity_id) }));
        form.put(`/fleet/compliance-items/${complianceItem.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${complianceItem.title}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit compliance item</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div><Label>Entity type *</Label><select value={data.entity_type} onChange={(e) => setData('entity_type', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm">{entityTypes.map((e) => <option key={e.value} value={e.value}>{e.name}</option>)}</select></div>
                    <div><Label>Entity ID *</Label><Input type="number" min={1} value={data.entity_id} onChange={(e) => setData('entity_id', e.target.value)} className="mt-1" /></div>
                    <div><Label>Compliance type *</Label><Input value={data.compliance_type} onChange={(e) => setData('compliance_type', e.target.value)} className="mt-1" /></div>
                    <div><Label>Title *</Label><Input value={data.title} onChange={(e) => setData('title', e.target.value)} className="mt-1" /></div>
                    <div><Label>Expiry date *</Label><Input type="date" value={data.expiry_date} onChange={(e) => setData('expiry_date', e.target.value)} className="mt-1" /></div>
                    <div><Label>Status</Label><select value={data.status} onChange={(e) => setData('status', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm">{statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}</select></div>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Update</Button><Button variant="outline" asChild><Link href={`/fleet/compliance-items/${complianceItem.id}`}>Cancel</Link></Button></div>
                </form>
            </div>
        </AppLayout>
    );
}
