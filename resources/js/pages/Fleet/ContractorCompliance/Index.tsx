import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { FileCheck, Pencil, Plus, Trash2 } from 'lucide-react';

interface Row {
    id: number;
    compliance_type: string;
    status?: string;
    reference_number?: string;
    issue_date?: string;
    expiry_date?: string;
    contractor?: { id: number; name: string };
}
interface Props {
    contractorCompliance: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    contractors: { id: number; name: string }[];
    complianceTypes: { value: string; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetContractorComplianceIndex({
    contractorCompliance,
    contractors: _contractors,
    complianceTypes,
    statuses,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        {
            title: 'Contractor compliance',
            href: '/fleet/contractor-compliance',
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Contractor compliance" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Contractor compliance
                    </h1>
                    <Button asChild>
                        <Link href="/fleet/contractor-compliance/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>
                {contractorCompliance.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <FileCheck className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No contractor compliance records yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/contractor-compliance/create">
                                Add
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">
                                            Contractor
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Type
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Status
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Reference
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Expiry
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {contractorCompliance.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                {row.contractor?.name ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {complianceTypes.find(
                                                    (c) =>
                                                        c.value ===
                                                        row.compliance_type,
                                                )?.name ?? row.compliance_type}
                                            </td>
                                            <td className="p-3">
                                                {statuses.find(
                                                    (s) =>
                                                        s.value === row.status,
                                                )?.name ??
                                                    row.status ??
                                                    '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.reference_number ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.expiry_date
                                                    ? new Date(
                                                          row.expiry_date,
                                                      ).toLocaleDateString()
                                                    : '—'}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/contractor-compliance/${row.id}`}
                                                    >
                                                        View
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/contractor-compliance/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/contractor-compliance/${row.id}`}
                                                    method="delete"
                                                    className="ml-2 inline"
                                                    onSubmit={(e) => {
                                                        if (!confirm('Delete?'))
                                                            e.preventDefault();
                                                    }}
                                                >
                                                    <Button
                                                        type="submit"
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        <Trash2 className="size-3.5 text-destructive" />
                                                    </Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {contractorCompliance.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {contractorCompliance.links.map((link, i) => (
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
