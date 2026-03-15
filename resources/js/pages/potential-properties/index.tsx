import { Badge } from '@/components/ui/badge';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Building2 } from 'lucide-react';

interface PotentialProperty {
    id: number;
    title: string;
    suburb: string | null;
    state: string | null;
    developer_name: string | null;
    estimated_price_min: number | null;
    estimated_price_max: number | null;
    status: string;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedData {
    data: PotentialProperty[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    properties: PaginatedData;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Properties', href: '/projects' },
    { title: 'Potential Properties', href: '/potential-properties' },
];

function formatPrice(value: number | null): string {
    if (value === null) return '—';
    return new Intl.NumberFormat('en-AU', {
        style: 'currency',
        currency: 'AUD',
        maximumFractionDigits: 0,
    }).format(value);
}

export default function PotentialPropertiesIndexPage({ properties }: Props) {
    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Potential Properties" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="potential-properties-page"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Potential Properties
                    </h1>
                    <p className="text-muted-foreground">
                        {properties.total} results
                    </p>
                </div>

                {properties.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                        <div className="rounded-full bg-muted p-4">
                            <Building2 className="size-8 text-muted-foreground" />
                        </div>
                        <div>
                            <p className="font-medium">
                                No potential properties
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Properties will appear here once added.
                            </p>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-lg border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="px-4 py-3 text-left font-medium">
                                            Title
                                        </th>
                                        <th className="px-4 py-3 text-left font-medium">
                                            Location
                                        </th>
                                        <th className="px-4 py-3 text-left font-medium">
                                            Developer
                                        </th>
                                        <th className="px-4 py-3 text-left font-medium">
                                            Est. Price Range
                                        </th>
                                        <th className="px-4 py-3 text-left font-medium">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-left font-medium">
                                            Created
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {properties.data.map((property) => (
                                        <tr
                                            key={property.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {property.title}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {[
                                                    property.suburb,
                                                    property.state,
                                                ]
                                                    .filter(Boolean)
                                                    .join(', ') || '—'}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {property.developer_name ??
                                                    '—'}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {formatPrice(
                                                    property.estimated_price_min,
                                                )}{' '}
                                                &ndash;{' '}
                                                {formatPrice(
                                                    property.estimated_price_max,
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="outline">
                                                    {property.status}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {new Date(
                                                    property.created_at,
                                                ).toLocaleDateString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {properties.last_page > 1 && (
                            <div className="flex items-center justify-center gap-1">
                                {properties.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url ?? '#'}
                                        className={`rounded-md px-3 py-1.5 text-sm ${
                                            link.active
                                                ? 'bg-primary text-primary-foreground'
                                                : link.url
                                                  ? 'text-muted-foreground hover:bg-muted'
                                                  : 'cursor-not-allowed text-muted-foreground/50'
                                        }`}
                                        preserveScroll
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppSidebarLayout>
    );
}
