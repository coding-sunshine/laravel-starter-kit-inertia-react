import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Option {
    value: string;
    name: string;
}

interface Props {
    statuses: Option[];
    licenseStatuses: Option[];
    riskCategories: Option[];
    complianceStatuses: Option[];
}

export default function FleetDriversCreate({
    statuses,
    licenseStatuses,
    riskCategories,
    complianceStatuses,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        employee_id: '',
        license_number: '',
        license_expiry_date: '',
        license_status: 'valid',
        status: 'active',
        compliance_status: '' as string,
        risk_category: '' as string,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/drivers' },
        { title: 'Drivers', href: '/fleet/drivers' },
        { title: 'Create', href: '/fleet/drivers/create' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New driver" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New driver</h1>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/fleet/drivers', {
                            transform: (d) => ({
                                ...d,
                                compliance_status: d.compliance_status || null,
                                risk_category: d.risk_category || null,
                            }),
                        });
                    }}
                    className="max-w-xl space-y-4"
                >
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="first_name">First name *</Label>
                            <Input id="first_name" value={data.first_name} onChange={(e) => setData('first_name', e.target.value)} className="mt-1" />
                            {errors.first_name && <p className="mt-1 text-sm text-destructive">{errors.first_name}</p>}
                        </div>
                        <div>
                            <Label htmlFor="last_name">Last name *</Label>
                            <Input id="last_name" value={data.last_name} onChange={(e) => setData('last_name', e.target.value)} className="mt-1" />
                            {errors.last_name && <p className="mt-1 text-sm text-destructive">{errors.last_name}</p>}
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="email">Email</Label>
                        <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className="mt-1" />
                        {errors.email && <p className="mt-1 text-sm text-destructive">{errors.email}</p>}
                    </div>
                    <div>
                        <Label htmlFor="phone">Phone</Label>
                        <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} className="mt-1" />
                        {errors.phone && <p className="mt-1 text-sm text-destructive">{errors.phone}</p>}
                    </div>
                    <div>
                        <Label htmlFor="employee_id">Employee ID</Label>
                        <Input id="employee_id" value={data.employee_id} onChange={(e) => setData('employee_id', e.target.value)} className="mt-1" />
                        {errors.employee_id && <p className="mt-1 text-sm text-destructive">{errors.employee_id}</p>}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="license_number">License number *</Label>
                            <Input id="license_number" value={data.license_number} onChange={(e) => setData('license_number', e.target.value)} className="mt-1" />
                            {errors.license_number && <p className="mt-1 text-sm text-destructive">{errors.license_number}</p>}
                        </div>
                        <div>
                            <Label htmlFor="license_expiry_date">License expiry *</Label>
                            <Input id="license_expiry_date" type="date" value={data.license_expiry_date} onChange={(e) => setData('license_expiry_date', e.target.value)} className="mt-1" />
                            {errors.license_expiry_date && <p className="mt-1 text-sm text-destructive">{errors.license_expiry_date}</p>}
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="license_status">License status *</Label>
                        <select id="license_status" value={data.license_status} onChange={(e) => setData('license_status', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm">
                            {licenseStatuses.map((o) => (<option key={o.value} value={o.value}>{o.name}</option>))}
                        </select>
                        {errors.license_status && <p className="mt-1 text-sm text-destructive">{errors.license_status}</p>}
                    </div>
                    <div>
                        <Label htmlFor="status">Status *</Label>
                        <select id="status" value={data.status} onChange={(e) => setData('status', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm">
                            {statuses.map((o) => (<option key={o.value} value={o.value}>{o.name}</option>))}
                        </select>
                        {errors.status && <p className="mt-1 text-sm text-destructive">{errors.status}</p>}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="compliance_status">Compliance status</Label>
                            <select id="compliance_status" value={data.compliance_status} onChange={(e) => setData('compliance_status', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm">
                                <option value="">—</option>
                                {complianceStatuses.map((o) => (<option key={o.value} value={o.value}>{o.name}</option>))}
                            </select>
                            {errors.compliance_status && <p className="mt-1 text-sm text-destructive">{errors.compliance_status}</p>}
                        </div>
                        <div>
                            <Label htmlFor="risk_category">Risk category</Label>
                            <select id="risk_category" value={data.risk_category} onChange={(e) => setData('risk_category', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm">
                                <option value="">—</option>
                                {riskCategories.map((o) => (<option key={o.value} value={o.value}>{o.name}</option>))}
                            </select>
                            {errors.risk_category && <p className="mt-1 text-sm text-destructive">{errors.risk_category}</p>}
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>Create driver</Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/drivers">Back to drivers</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
