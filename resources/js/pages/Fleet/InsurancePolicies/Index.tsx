import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { FileText, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface InsurancePolicyRecord {
    id: number;
    policy_number: string;
    insurer_name: string;
    policy_type: string;
    start_date: string;
    end_date: string;
    status: string;
}
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
interface Props {
    insurancePolicies: { data: InsurancePolicyRecord[]; links: PaginationLink[] };
    policyTypes: { value: string; name: string }[];
    coverageTypes: { value: string; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetInsurancePoliciesIndex({
    insurancePolicies,
    policyTypes,
    coverageTypes,
    statuses,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/insurance-policies' },
        { title: 'Insurance policies', href: '/fleet/insurance-policies' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Insurance policies" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Insurance policies</h1>
                    <Button asChild>
                        <Link href="/fleet/insurance-policies/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>
                {insurancePolicies.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <FileText className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No insurance policies yet.</p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/insurance-policies/create">Create insurance policy</Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Policy number</th>
                                        <th className="p-3 text-left font-medium">Insurer</th>
                                        <th className="p-3 text-left font-medium">Policy type</th>
                                        <th className="p-3 text-left font-medium">Start date</th>
                                        <th className="p-3 text-left font-medium">End date</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {insurancePolicies.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">
                                                <Link
                                                    href={`/fleet/insurance-policies/${row.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {row.policy_number}
                                                </Link>
                                            </td>
                                            <td className="p-3">{row.insurer_name}</td>
                                            <td className="p-3">{row.policy_type}</td>
                                            <td className="p-3">
                                                {row.start_date ? new Date(row.start_date).toLocaleDateString() : '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.end_date ? new Date(row.end_date).toLocaleDateString() : '—'}
                                            </td>
                                            <td className="p-3">{row.status}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={`/fleet/insurance-policies/${row.id}`}>View</Link>
                                                </Button>
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={`/fleet/insurance-policies/${row.id}/edit`}>
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/insurance-policies/${row.id}`}
                                                    method="delete"
                                                    className="ml-2 inline"
                                                    onSubmit={(e) => {
                                                        if (!confirm('Delete?')) e.preventDefault();
                                                    }}
                                                >
                                                    <Button type="submit" variant="ghost" size="sm">
                                                        <Trash2 className="size-3.5 text-destructive" />
                                                    </Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {insurancePolicies.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {insurancePolicies.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url ?? '#'}
                                        className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                    >
                                        {link.label}
                                    </Link>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
