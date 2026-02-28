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
    incidents: { id: number; incident_number: string }[];
    insurancePolicies: { id: number; policy_number: string }[];
    claimTypes: Option[];
    statuses: Option[];
}

export default function FleetInsuranceClaimsCreate({
    incidents,
    insurancePolicies,
    claimTypes,
    statuses,
}: Props) {
    const form = useForm({
        incident_id: '' as number | '',
        insurance_policy_id: '' as number | '',
        claim_number: '',
        claim_type: claimTypes[0]?.value ?? '',
        status: statuses[0]?.value ?? '',
        photos: [] as File[],
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/insurance-claims' },
        { title: 'Insurance claims', href: '/fleet/insurance-claims' },
        { title: 'Create', href: '/fleet/insurance-claims/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            incident_id: d.incident_id === '' ? undefined : d.incident_id,
            insurance_policy_id:
                d.insurance_policy_id === '' ? undefined : d.insurance_policy_id,
        }));
        form.post('/fleet/insurance-claims', { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New insurance claim" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New insurance claim</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="incident_id">Incident *</Label>
                        <select
                            id="incident_id"
                            value={data.incident_id === '' ? '' : String(data.incident_id)}
                            onChange={(e) =>
                                setData(
                                    'incident_id',
                                    e.target.value === '' ? '' : Number(e.target.value)
                                )
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            required
                        >
                            <option value="">Select</option>
                            {incidents.map((i) => (
                                <option key={i.id} value={i.id}>
                                    {i.incident_number}
                                </option>
                            ))}
                        </select>
                        {errors.incident_id && (
                            <p className="mt-1 text-sm text-destructive">{errors.incident_id}</p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="insurance_policy_id">Insurance policy *</Label>
                        <select
                            id="insurance_policy_id"
                            value={
                                data.insurance_policy_id === ''
                                    ? ''
                                    : String(data.insurance_policy_id)
                            }
                            onChange={(e) =>
                                setData(
                                    'insurance_policy_id',
                                    e.target.value === '' ? '' : Number(e.target.value)
                                )
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            required
                        >
                            <option value="">Select</option>
                            {insurancePolicies.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.policy_number}
                                </option>
                            ))}
                        </select>
                        {errors.insurance_policy_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.insurance_policy_id}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="claim_number">Claim number *</Label>
                        <Input
                            id="claim_number"
                            value={data.claim_number}
                            onChange={(e) => setData('claim_number', e.target.value)}
                            className="mt-1"
                        />
                        {errors.claim_number && (
                            <p className="mt-1 text-sm text-destructive">{errors.claim_number}</p>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="claim_type">Claim type *</Label>
                            <select
                                id="claim_type"
                                value={data.claim_type}
                                onChange={(e) => setData('claim_type', e.target.value)}
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {claimTypes.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.name}
                                    </option>
                                ))}
                            </select>
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
                    </div>
                    <div>
                        <Label htmlFor="photos">Photos (optional, for AI damage analysis)</Label>
                        <input
                            id="photos"
                            type="file"
                            accept="image/*"
                            multiple
                            className="mt-1 block w-full text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground file:transition-colors"
                            onChange={(e) => setData('photos', e.target.files ? Array.from(e.target.files) : [])}
                        />
                        {data.photos.length > 0 && (
                            <p className="mt-1 text-sm text-muted-foreground">{data.photos.length} file(s) selected</p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Create insurance claim
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/insurance-claims">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
