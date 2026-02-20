import { GlossaryTerm } from '@/components/glossary-term';
import Heading from '@/components/heading';
import { RrmcsGuidance } from '@/components/rrmcs-guidance';
import { StatusPill } from '@/components/status-pill';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Train } from 'lucide-react';

interface Siding {
    id: number;
    code: string;
    name: string;
}

interface Rake {
    id: number;
    rake_number: string;
    rake_type: string | null;
    wagon_count: number;
    state: string;
    loading_start_time: string | null;
    loading_end_time: string | null;
    demurrage_hours: number;
    demurrage_penalty_amount: string;
    siding?: Siding;
}

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    rakes: {
        data: Rake[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        links: PaginatorLink[];
    };
}

export default function RakesIndex({ rakes }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Rakes', href: '/rakes' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rakes" />

            <div className="space-y-6">
                <Heading
                    title="Railway Rakes"
                    description="Manage railway rakes and wagons for the RRMCS system"
                />

                <RrmcsGuidance
                    title="What this section is for"
                    before="Rake status and 3-hour loading window tracked in Excel and stopwatch; demurrage and penalties found only after Railway Receipt (RR) arrives."
                    after="Rake list with live demurrage countdown; alerts at 60 min (amber), 30 min (red), 0 min (critical). Overload detection during weighment—24+ hours before RR."
                />

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Train className="h-5 w-5" />
                            Rakes
                        </CardTitle>
                        <CardDescription>
                            All railway rakes you have access to
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {rakes.data.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center">
                                <Train className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">
                                    No rakes yet
                                </p>
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Rake #
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Siding
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Type
                                                </th>
                                                <th className="px-5 py-3.5 text-right font-medium">
                                                    Wagons
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    State
                                                </th>
                                                <th className="min-w-[12rem] px-5 py-3.5 text-left font-medium">
                                                    Loading window
                                                </th>
                                                <th className="px-5 py-3.5 text-right font-medium">
                                                    <GlossaryTerm term="Demurrage">Demurrage</GlossaryTerm> (h)
                                                </th>
                                                <th className="px-5 py-3.5 text-right font-medium">
                                                    Penalty
                                                </th>
                                                <th className="px-5 py-3.5 text-right font-medium">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {rakes.data.map((rake) => (
                                                <tr
                                                    key={rake.id}
                                                    className="border-b last:border-0 hover:bg-muted/30"
                                                >
                                                    <td className="px-5 py-3.5 font-medium">
                                                        {rake.rake_number}
                                                    </td>
                                                    <td className="px-5 py-3.5 text-muted-foreground">
                                                        {rake.siding
                                                            ? `${rake.siding.code} (${rake.siding.name})`
                                                            : '—'}
                                                    </td>
                                                    <td className="px-5 py-3.5 text-muted-foreground">
                                                        {rake.rake_type ?? '—'}
                                                    </td>
                                                    <td className="px-5 py-3.5 text-right">
                                                        {rake.wagon_count}
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        <StatusPill
                                                            status={rake.state}
                                                        />
                                                    </td>
                                                    <td className="px-5 py-3.5 whitespace-nowrap text-muted-foreground">
                                                        {rake.loading_start_time &&
                                                        rake.loading_end_time
                                                            ? `${new Date(rake.loading_start_time).toLocaleString()} – ${new Date(rake.loading_end_time).toLocaleString()}`
                                                            : '—'}
                                                    </td>
                                                    <td className="px-5 py-3.5 text-right">
                                                        {rake.demurrage_hours}
                                                    </td>
                                                    <td className="px-5 py-3.5 text-right">
                                                        {
                                                            rake.demurrage_penalty_amount
                                                        }
                                                    </td>
                                                    <td className="px-5 py-3.5 text-right">
                                                        <Link
                                                            href={`/rakes/${rake.id}`}
                                                            className="text-sm font-medium text-primary underline underline-offset-4"
                                                        >
                                                            View
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {(rakes.prev_page_url ||
                                    rakes.next_page_url) && (
                                    <nav
                                        className="mt-6 flex items-center justify-center gap-4 pt-2"
                                        aria-label="Pagination"
                                    >
                                        {rakes.prev_page_url ? (
                                            <Link
                                                href={rakes.prev_page_url}
                                                className="text-sm font-medium text-foreground underline underline-offset-4"
                                            >
                                                Previous
                                            </Link>
                                        ) : null}
                                        <span className="text-sm text-muted-foreground">
                                            Page {rakes.current_page} of{' '}
                                            {rakes.last_page}
                                        </span>
                                        {rakes.next_page_url ? (
                                            <Link
                                                href={rakes.next_page_url}
                                                className="text-sm font-medium text-foreground underline underline-offset-4"
                                            >
                                                Next
                                            </Link>
                                        ) : null}
                                    </nav>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
