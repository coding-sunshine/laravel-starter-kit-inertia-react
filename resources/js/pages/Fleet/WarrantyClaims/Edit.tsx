import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Option {
    value: string;
    name: string;
}
interface ClaimRecord {
    id: number;
    work_order_id: number;
    claim_number: string;
    status: string;
    claim_amount?: string | number | null;
    settlement_amount?: string | number | null;
    submitted_date?: string | null;
    settled_at?: string | null;
}
interface Props {
    warrantyClaim: ClaimRecord;
    workOrders: { id: number; work_order_number: string; title: string }[];
    statuses: Option[];
}

export default function FleetWarrantyClaimsEdit({
    warrantyClaim,
    workOrders,
    statuses,
}: Props) {
    const form = useForm({
        work_order_id: warrantyClaim.work_order_id,
        claim_number: warrantyClaim.claim_number,
        status: warrantyClaim.status,
        claim_amount:
            warrantyClaim.claim_amount != null
                ? String(warrantyClaim.claim_amount)
                : '',
        settlement_amount:
            warrantyClaim.settlement_amount != null
                ? String(warrantyClaim.settlement_amount)
                : '',
        submitted_date: warrantyClaim.submitted_date?.slice(0, 10) ?? '',
        settled_at: warrantyClaim.settled_at?.slice(0, 10) ?? '',
    });
    const { data, setData, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Warranty claims', href: '/fleet/warranty-claims' },
        {
            title: warrantyClaim.claim_number,
            href: `/fleet/warranty-claims/${warrantyClaim.id}`,
        },
        {
            title: 'Edit',
            href: `/fleet/warranty-claims/${warrantyClaim.id}/edit`,
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            claim_amount: d.claim_amount === '' ? null : d.claim_amount,
            settlement_amount:
                d.settlement_amount === '' ? null : d.settlement_amount,
            submitted_date: d.submitted_date || null,
            settled_at: d.settled_at || null,
            _method: 'PUT',
        }));
        form.post(`/fleet/warranty-claims/${warrantyClaim.id}`, {
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${warrantyClaim.claim_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit warranty claim</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label>Work order *</Label>
                        <select
                            value={data.work_order_id}
                            onChange={(e) =>
                                setData('work_order_id', Number(e.target.value))
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            required
                        >
                            {workOrders.map((wo) => (
                                <option key={wo.id} value={wo.id}>
                                    {wo.work_order_number} – {wo.title}
                                </option>
                            ))}
                        </select>
                        {errors.work_order_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.work_order_id}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label>Claim number *</Label>
                        <Input
                            value={data.claim_number}
                            onChange={(e) =>
                                setData('claim_number', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.claim_number && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.claim_number}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label>Status *</Label>
                        <select
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {statuses.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Update
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link
                                href={`/fleet/warranty-claims/${warrantyClaim.id}`}
                            >
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
