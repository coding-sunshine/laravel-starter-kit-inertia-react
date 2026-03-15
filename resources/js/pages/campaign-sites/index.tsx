import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Globe, Plus } from 'lucide-react';

interface CampaignSite {
    id: number;
    title: string;
    site_id: string;
    puck_enabled: boolean;
    primary_color: string | null;
    created_at: string;
}

interface PaginatedSites {
    data: CampaignSite[];
    total?: number;
    current_page?: number;
    last_page?: number;
    meta?: { total: number; current_page: number; last_page: number };
}

interface Props {
    sites: PaginatedSites;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Marketing', href: '/campaign-sites' },
    { title: 'Campaign Sites', href: '/campaign-sites' },
];

export default function CampaignSitesIndexPage({ sites }: Props) {
    const total = sites?.total ?? sites?.meta?.total ?? sites?.data?.length ?? 0;

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Campaign Sites" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4" data-pan="campaign-sites-index">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Campaign Sites</h1>
                        <p className="text-muted-foreground">{total} campaign sites</p>
                    </div>
                    <Link
                        href="/campaign-sites/create"
                        className="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        data-pan="campaign-sites-create"
                    >
                        <Plus className="h-4 w-4" />
                        New Campaign Site
                    </Link>
                </div>

                {(sites?.data ?? []).length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center">
                        <Globe className="mb-4 h-12 w-12 text-muted-foreground" />
                        <h2 className="text-lg font-semibold">No campaign sites yet</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create your first campaign site to get started.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {(sites?.data ?? []).map((site) => (
                            <div
                                key={site.id}
                                className="rounded-lg border bg-card p-4 shadow-sm"
                            >
                                <div className="mb-3 flex items-start justify-between">
                                    <div>
                                        <h3 className="font-semibold">{site.title}</h3>
                                        <p className="text-xs text-muted-foreground">{site.site_id}</p>
                                    </div>
                                    {site.puck_enabled && (
                                        <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">
                                            Published
                                        </span>
                                    )}
                                </div>
                                <div className="flex gap-2">
                                    <Link
                                        href={`/campaign-sites/${site.id}/edit-puck`}
                                        className="inline-flex items-center gap-1 rounded bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90"
                                        data-pan="campaign-sites-edit-puck"
                                    >
                                        Edit with Puck
                                    </Link>
                                    <a
                                        href={`/w/${site.site_id}`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1 rounded border px-3 py-1.5 text-xs font-medium hover:bg-accent"
                                    >
                                        Preview
                                    </a>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppSidebarLayout>
    );
}
