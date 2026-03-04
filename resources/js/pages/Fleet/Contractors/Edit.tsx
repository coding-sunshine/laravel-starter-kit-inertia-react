import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

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
interface Props {
    contractor: Contractor;
    statuses: { value: string; name: string }[];
}

export default function FleetContractorsEdit({ contractor, statuses }: Props) {
    const form = useForm({
        name: contractor.name,
        code: contractor.code ?? '',
        contractor_type: contractor.contractor_type ?? '',
        status: contractor.status ?? 'active',
        contact_name: contractor.contact_name ?? '',
        contact_phone: contractor.contact_phone ?? '',
        contact_email: contractor.contact_email ?? '',
        address: contractor.address ?? '',
        postcode: contractor.postcode ?? '',
        city: contractor.city ?? '',
        tax_number: contractor.tax_number ?? '',
        insurance_reference: contractor.insurance_reference ?? '',
        insurance_expiry: contractor.insurance_expiry?.slice(0, 10) ?? '',
        notes: contractor.notes ?? '',
        is_active: contractor.is_active ?? true,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Contractors', href: '/fleet/contractors' },
        { title: 'Edit', href: `/fleet/contractors/${contractor.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit contractor" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/fleet/contractors/${contractor.id}`}>
                            Back
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">Edit contractor</h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put(`/fleet/contractors/${contractor.id}`);
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Name</Label>
                            <Input
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Code</Label>
                            <Input
                                value={form.data.code}
                                onChange={(e) =>
                                    form.setData('code', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Contractor type</Label>
                            <Input
                                value={form.data.contractor_type}
                                onChange={(e) =>
                                    form.setData(
                                        'contractor_type',
                                        e.target.value,
                                    )
                                }
                            />
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
                    </div>
                    <div className="space-y-2">
                        <Label>Contact name</Label>
                        <Input
                            value={form.data.contact_name}
                            onChange={(e) =>
                                form.setData('contact_name', e.target.value)
                            }
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Contact phone</Label>
                            <Input
                                value={form.data.contact_phone}
                                onChange={(e) =>
                                    form.setData(
                                        'contact_phone',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Contact email</Label>
                            <Input
                                type="email"
                                value={form.data.contact_email}
                                onChange={(e) =>
                                    form.setData(
                                        'contact_email',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Address</Label>
                        <Input
                            value={form.data.address}
                            onChange={(e) =>
                                form.setData('address', e.target.value)
                            }
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>City</Label>
                            <Input
                                value={form.data.city}
                                onChange={(e) =>
                                    form.setData('city', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Postcode</Label>
                            <Input
                                value={form.data.postcode}
                                onChange={(e) =>
                                    form.setData('postcode', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Tax number</Label>
                            <Input
                                value={form.data.tax_number}
                                onChange={(e) =>
                                    form.setData('tax_number', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Insurance reference</Label>
                            <Input
                                value={form.data.insurance_reference}
                                onChange={(e) =>
                                    form.setData(
                                        'insurance_reference',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Insurance expiry</Label>
                        <Input
                            type="date"
                            value={form.data.insurance_expiry}
                            onChange={(e) =>
                                form.setData('insurance_expiry', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Notes</Label>
                        <textarea
                            value={form.data.notes}
                            onChange={(e) =>
                                form.setData('notes', e.target.value)
                            }
                            className="min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="is_active"
                            checked={form.data.is_active}
                            onChange={(e) =>
                                form.setData('is_active', e.target.checked)
                            }
                        />
                        <Label htmlFor="is_active">Active</Label>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Update
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href={`/fleet/contractors/${contractor.id}`}>
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
