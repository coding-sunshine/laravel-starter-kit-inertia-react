import { GlossaryTerm } from '@/components/glossary-term';
import Heading from '@/components/heading';
import { RrmcsGuidance } from '@/components/rrmcs-guidance';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { FileText, Plus } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Indent {
    id: number;
    indent_number: string;
    target_quantity_mt: string;
    allocated_quantity_mt: string;
    state: string;
    indent_date: string;
    required_by_date: string | null;
    e_demand_reference_id: string | null;
    fnr_number: string | null;
    indent_confirmation_pdf_url?: string | null;
    siding?: Siding | null;
}

interface PaginatedIndents {
    data: Indent[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    indents: PaginatedIndents;
    sidings: Siding[];
}

export default function IndentsIndex({ indents }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Indents', href: '/indents' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Indents" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Rake Indents"
                        description="Manage rake orders and requests for the RRMCS system"
                    />
                    <Link href="/indents/create">
                        <Button size="sm">
                            <Plus className="mr-2 size-4" />
                            Create indent
                        </Button>
                    </Link>
                </div>

                <RrmcsGuidance
                    title="What this section is for"
                    before="Indent requests raised on paper or Excel; 30+ minutes to prepare and submit; stock and allocations tracked manually."
                    after="Create indents in the app with target quantity and date; attach e-Demand confirmation PDF. Track status: pending → allocated → completed."
                />

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            Indents
                        </CardTitle>
                        <CardDescription>
                            View and manage all rake indents (orders). e-Demand
                            reference and FNR shown when set.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {indents.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="pr-4 pb-2 font-medium">
                                                Indent number
                                            </th>
                                            <th className="pr-4 pb-2 font-medium">
                                                Siding
                                            </th>
                                            <th className="pr-4 pb-2 font-medium">
                                                Target (MT)
                                            </th>
                                            <th className="pr-4 pb-2 font-medium">
                                                State
                                            </th>
                                            <th className="pr-4 pb-2 font-medium">
                                                Indent date
                                            </th>
                                            <th className="pr-4 pb-2 font-medium">
                                                <GlossaryTerm term="e-Demand">
                                                    e-Demand
                                                </GlossaryTerm>{' '}
                                                ref
                                            </th>
                                            <th className="pr-4 pb-2 font-medium">
                                                <GlossaryTerm term="FNR">
                                                    FNR
                                                </GlossaryTerm>
                                            </th>
                                            <th className="pb-2 font-medium">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {indents.data.map((indent) => (
                                            <tr
                                                key={indent.id}
                                                className="border-b"
                                            >
                                                <td className="py-2 pr-4">
                                                    <Link
                                                        href={`/indents/${indent.id}`}
                                                        className="font-medium underline underline-offset-2"
                                                    >
                                                        {indent.indent_number}
                                                    </Link>
                                                </td>
                                                <td className="py-2 pr-4 text-muted-foreground">
                                                    {indent.siding?.name ?? '—'}
                                                </td>
                                                <td className="py-2 pr-4">
                                                    {indent.target_quantity_mt}
                                                </td>
                                                <td className="py-2 pr-4 capitalize">
                                                    {indent.state}
                                                </td>
                                                <td className="py-2 pr-4 text-muted-foreground">
                                                    {new Date(
                                                        indent.indent_date,
                                                    ).toLocaleDateString()}
                                                </td>
                                                <td className="py-2 pr-4 text-muted-foreground">
                                                    {indent.e_demand_reference_id ??
                                                        '—'}
                                                </td>
                                                <td className="py-2 pr-4 text-muted-foreground">
                                                    {indent.fnr_number ?? '—'}
                                                </td>
                                                <td className="py-2">
                                                    <Link
                                                        href={`/indents/${indent.id}`}
                                                    >
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                        >
                                                            View
                                                        </Button>
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                {indents.last_page > 1 && (
                                    <div className="mt-4 flex flex-wrap gap-2">
                                        {indents.links.map((link, index) => (
                                            <Link
                                                key={`${link.url ?? 'null'}-${link.label}-${index}`}
                                                href={link.url ?? '#'}
                                                className={
                                                    link.active
                                                        ? 'rounded border bg-muted px-2 py-1 text-sm font-medium'
                                                        : 'rounded border px-2 py-1 text-sm'
                                                }
                                            >
                                                {link.label}
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="rounded-lg border border-dashed p-8 text-center">
                                <FileText className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">
                                    No indents yet. Create one to get started.
                                </p>
                                <Link href="/indents/create">
                                    <Button className="mt-4" size="sm">
                                        Create indent
                                    </Button>
                                </Link>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
