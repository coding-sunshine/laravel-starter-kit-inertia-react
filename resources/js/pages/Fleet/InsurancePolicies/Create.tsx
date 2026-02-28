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
    policyTypes: Option[];
    coverageTypes: Option[];
    statuses: Option[];
}

export default function FleetInsurancePoliciesCreate({
    policyTypes,
    coverageTypes,
    statuses,
}: Props) {
    const form = useForm({
        policy_number: '',
        insurer_name: '',
        policy_type: policyTypes[0]?.value ?? '',
        coverage_type: coverageTypes[0]?.value ?? '',
        start_date: '',
        end_date: '',
        premium_amount: '' as string | number,
        excess_amount: '' as string | number,
        status: statuses[0]?.value ?? '',
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/insurance-policies' },
        { title: 'Insurance policies', href: '/fleet/insurance-policies' },
        { title: 'Create', href: '/fleet/insurance-policies/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            premium_amount: d.premium_amount === '' ? undefined : Number(d.premium_amount),
            excess_amount: d.excess_amount === '' ? undefined : Number(d.excess_amount),
        }));
        form.post('/fleet/insurance-policies');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New insurance policy" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New insurance policy</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="policy_number">Policy number *</Label>
                        <Input
                            id="policy_number"
                            value={data.policy_number}
                            onChange={(e) => setData('policy_number', e.target.value)}
                            className="mt-1"
                        />
                        {errors.policy_number && (
                            <p className="mt-1 text-sm text-destructive">{errors.policy_number}</p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="insurer_name">Insurer name *</Label>
                        <Input
                            id="insurer_name"
                            value={data.insurer_name}
                            onChange={(e) => setData('insurer_name', e.target.value)}
                            className="mt-1"
                        />
                        {errors.insurer_name && (
                            <p className="mt-1 text-sm text-destructive">{errors.insurer_name}</p>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="policy_type">Policy type *</Label>
                            <select
                                id="policy_type"
                                value={data.policy_type}
                                onChange={(e) => setData('policy_type', e.target.value)}
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {policyTypes.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="coverage_type">Coverage type *</Label>
                            <select
                                id="coverage_type"
                                value={data.coverage_type}
                                onChange={(e) => setData('coverage_type', e.target.value)}
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {coverageTypes.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="start_date">Start date *</Label>
                            <Input
                                id="start_date"
                                type="date"
                                value={data.start_date}
                                onChange={(e) => setData('start_date', e.target.value)}
                                className="mt-1"
                            />
                            {errors.start_date && (
                                <p className="mt-1 text-sm text-destructive">{errors.start_date}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="end_date">End date *</Label>
                            <Input
                                id="end_date"
                                type="date"
                                value={data.end_date}
                                onChange={(e) => setData('end_date', e.target.value)}
                                className="mt-1"
                            />
                            {errors.end_date && (
                                <p className="mt-1 text-sm text-destructive">{errors.end_date}</p>
                            )}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="premium_amount">Premium amount</Label>
                            <Input
                                id="premium_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.premium_amount === '' ? '' : data.premium_amount}
                                onChange={(e) =>
                                    setData('premium_amount', e.target.value === '' ? '' : e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.premium_amount && (
                                <p className="mt-1 text-sm text-destructive">{errors.premium_amount}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="excess_amount">Excess amount</Label>
                            <Input
                                id="excess_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.excess_amount === '' ? '' : data.excess_amount}
                                onChange={(e) =>
                                    setData('excess_amount', e.target.value === '' ? '' : e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.excess_amount && (
                                <p className="mt-1 text-sm text-destructive">{errors.excess_amount}</p>
                            )}
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="status">Status *</Label>
                        <select
                            id="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {statuses.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Create insurance policy
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/insurance-policies">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
