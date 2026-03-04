import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Target, Trash2 } from 'lucide-react';

interface CarbonTargetRecord {
    id: number;
    name: string;
    period: string;
    target_year: number;
    target_co2_kg: string | number;
    is_active: boolean;
}
interface Option {
    value: string;
    name: string;
}
interface Props {
    carbonTargets: {
        data: CarbonTargetRecord[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters?: Record<string, string>;
    periods: Option[];
}

export default function FleetCarbonTargetsIndex({
    carbonTargets,
    periods,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Carbon targets', href: '/fleet/carbon-targets' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Carbon targets" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Carbon targets</h1>
                    <Button asChild>
                        <Link href="/fleet/carbon-targets/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>
                <form
                    method="get"
                    className="flex flex-wrap items-end gap-4 rounded-lg border p-4"
                >
                    <div className="space-y-1">
                        <Label>Period</Label>
                        <select
                            name="period"
                            defaultValue=""
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {periods.map((p) => (
                                <option key={p.value} value={p.value}>
                                    {p.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">
                        Filter
                    </Button>
                </form>
                {carbonTargets.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Target className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No carbon targets yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/carbon-targets/create">
                                Create carbon target
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">
                                            Name
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Period
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Target year
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Target CO₂ (kg)
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Active
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {carbonTargets.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                <Link
                                                    href={`/fleet/carbon-targets/${row.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {row.name}
                                                </Link>
                                            </td>
                                            <td className="p-3">
                                                {row.period}
                                            </td>
                                            <td className="p-3">
                                                {row.target_year}
                                            </td>
                                            <td className="p-3">
                                                {String(row.target_co2_kg)}
                                            </td>
                                            <td className="p-3">
                                                {row.is_active ? 'Yes' : 'No'}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/carbon-targets/${row.id}`}
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
                                                        href={`/fleet/carbon-targets/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/carbon-targets/${row.id}`}
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
                        {carbonTargets.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {carbonTargets.links.map((link, i) => (
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
