import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Props {
    contractors: { id: number; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetContractorInvoicesCreate({
    contractors,
    statuses,
}: Props) {
    const form = useForm({
        contractor_id: '' as number | '',
        invoice_number: '',
        invoice_date: new Date().toISOString().slice(0, 10),
        due_date: '',
        subtotal: '',
        tax_amount: '',
        total_amount: '',
        status: 'pending',
        work_order_reference: '',
        description: '',
        paid_date: '',
        payment_reference: '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Contractor invoices', href: '/fleet/contractor-invoices' },
        { title: 'New', href: '/fleet/contractor-invoices/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New contractor invoice" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/contractor-invoices">Back</Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        New contractor invoice
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/fleet/contractor-invoices');
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>Contractor</Label>
                        <select
                            required
                            value={form.data.contractor_id}
                            onChange={(e) =>
                                form.setData(
                                    'contractor_id',
                                    e.target.value
                                        ? Number(e.target.value)
                                        : '',
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">—</option>
                            {contractors.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Invoice number</Label>
                        <Input
                            value={form.data.invoice_number}
                            onChange={(e) =>
                                form.setData('invoice_number', e.target.value)
                            }
                            required
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Invoice date</Label>
                            <Input
                                type="date"
                                required
                                value={form.data.invoice_date}
                                onChange={(e) =>
                                    form.setData('invoice_date', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Due date</Label>
                            <Input
                                type="date"
                                value={form.data.due_date}
                                onChange={(e) =>
                                    form.setData('due_date', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="space-y-2">
                            <Label>Subtotal</Label>
                            <Input
                                type="number"
                                step="any"
                                min={0}
                                value={form.data.subtotal}
                                onChange={(e) =>
                                    form.setData('subtotal', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Tax amount</Label>
                            <Input
                                type="number"
                                step="any"
                                min={0}
                                value={form.data.tax_amount}
                                onChange={(e) =>
                                    form.setData('tax_amount', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Total amount</Label>
                            <Input
                                type="number"
                                step="any"
                                min={0}
                                required
                                value={form.data.total_amount}
                                onChange={(e) =>
                                    form.setData('total_amount', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Status</Label>
                        <select
                            value={form.data.status}
                            onChange={(e) =>
                                form.setData('status', e.target.value)
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {statuses.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Work order reference</Label>
                        <Input
                            value={form.data.work_order_reference}
                            onChange={(e) =>
                                form.setData(
                                    'work_order_reference',
                                    e.target.value,
                                )
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Description</Label>
                        <textarea
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                            className="min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Paid date</Label>
                            <Input
                                type="date"
                                value={form.data.paid_date}
                                onChange={(e) =>
                                    form.setData('paid_date', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Payment reference</Label>
                            <Input
                                value={form.data.payment_reference}
                                onChange={(e) =>
                                    form.setData(
                                        'payment_reference',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/fleet/contractor-invoices">
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
