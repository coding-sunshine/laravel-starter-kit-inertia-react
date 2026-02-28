import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { FileCheck, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface InsuranceClaimRecord {
    id: number;
    claim_number: string;
    claim_type: string;
    status: string;
    incident?: { id: number; incident_number: string };
    insurance_policy?: { id: number; policy_number: string };
}
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
interface Props {
    insuranceClaims: { data: InsuranceClaimRecord[]; links: PaginationLink[] };
    incidents: { id: number; incident_number: string }[];
    insurancePolicies: { id: number; policy_number: string }[];
    claimTypes: { value: string; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetInsuranceClaimsIndex({
    insuranceClaims,
    incidents,
    insurancePolicies,
    claimTypes,
    statuses,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/insurance-claims' },
        { title: 'Insurance claims', href: '/fleet/insurance-claims' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Insurance claims" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Insurance claims</h1>
                    <Button asChild>
                        <Link href="/fleet/insurance-claims/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>
                {insuranceClaims.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <FileCheck className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No insurance claims yet.</p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/insurance-claims/create">Create insurance claim</Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Claim number</th>
                                        <th className="p-3 text-left font-medium">Incident</th>
                                        <th className="p-3 text-left font-medium">Policy</th>
                                        <th className="p-3 text-left font-medium">Claim type</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {insuranceClaims.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">
                                                <Link
                                                    href={`/fleet/insurance-claims/${row.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {row.claim_number}
                                                </Link>
                                            </td>
                                            <td className="p-3">
                                                {row.incident ? (
                                                    <Link
                                                        href={`/fleet/incidents/${row.incident.id}`}
                                                        className="underline"
                                                    >
                                                        {row.incident.incident_number}
                                                    </Link>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="p-3">
                                                {row.insurance_policy ? (
                                                    <Link
                                                        href={`/fleet/insurance-policies/${row.insurance_policy.id}`}
                                                        className="underline"
                                                    >
                                                        {row.insurance_policy.policy_number}
                                                    </Link>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="p-3">{row.claim_type}</td>
                                            <td className="p-3">{row.status}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={`/fleet/insurance-claims/${row.id}`}>
                                                        View
                                                    </Link>
                                                </Button>
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={`/fleet/insurance-claims/${row.id}/edit`}>
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/insurance-claims/${row.id}`}
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
                        {insuranceClaims.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {insuranceClaims.links.map((link, i) => (
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
