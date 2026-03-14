import { DataTable } from '@/components/data-table/data-table';
import type {
    DataTableResponse,
} from '@/components/data-table/types';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Building2, MapPin } from 'lucide-react';

interface LotTableRow {
    id: number;
    lot_number: string | null;
    status: string | null;
    price: number | null;
    project_id: number | null;
    created_at: string | null;
}

interface ProjectTableRow {
    id: number;
    name: string | null;
    status: string | null;
    created_at: string | null;
}

interface Props {
    lotsTableData?: DataTableResponse<LotTableRow>;
    lotsSearchableColumns: string[];
    projectsTableData?: DataTableResponse<ProjectTableRow>;
    projectsSearchableColumns: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/member-listings' },
    { title: 'Member Listings', href: '/member-listings' },
];

export default function MemberListingsIndexPage({
    lotsTableData,
    lotsSearchableColumns = [],
    projectsTableData,
    projectsSearchableColumns = [],
}: Props) {
    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Member Listings" />
            <div
                className="flex h-full flex-1 flex-col gap-6 p-4"
                data-pan="member-listings-index"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Member Listings
                    </h1>
                    <p className="text-muted-foreground">
                        View all projects and lots available to members
                    </p>
                </div>

                <div className="space-y-6">
                    <section>
                        <div className="mb-3 flex items-center gap-2">
                            <Building2 className="size-5 text-muted-foreground" />
                            <h2 className="text-lg font-semibold">Projects</h2>
                            {projectsTableData && (
                                <span className="text-sm text-muted-foreground">
                                    ({projectsTableData.meta.total} total)
                                </span>
                            )}
                        </div>
                        <DataTable<ProjectTableRow>
                            tableData={projectsTableData}
                            tableName="member-listings-projects"
                            searchableColumns={projectsSearchableColumns}
                            debounceMs={300}
                            partialReloadKey="projectsTableData"
                            emptyState={
                                <div className="flex flex-col items-center justify-center gap-4 py-12 text-center">
                                    <div className="rounded-full bg-muted p-4">
                                        <Building2 className="size-8 text-muted-foreground" />
                                    </div>
                                    <div>
                                        <p className="font-medium">
                                            No projects found
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Try adjusting your search or
                                            filters.
                                        </p>
                                    </div>
                                </div>
                            }
                            options={{
                                columnVisibility: true,
                                columnOrdering: true,
                                filters: true,
                                exports: true,
                            }}
                            translations={{
                                noData: 'No projects',
                                search: 'Search projects',
                                clearAllFilters: 'Clear all filters',
                                density: 'Row density',
                                selectAllMatching: (count) =>
                                    `Select all ${count} projects`,
                            }}
                        />
                    </section>

                    <section>
                        <div className="mb-3 flex items-center gap-2">
                            <MapPin className="size-5 text-muted-foreground" />
                            <h2 className="text-lg font-semibold">Lots</h2>
                            {lotsTableData && (
                                <span className="text-sm text-muted-foreground">
                                    ({lotsTableData.meta.total} total)
                                </span>
                            )}
                        </div>
                        <DataTable<LotTableRow>
                            tableData={lotsTableData}
                            tableName="member-listings-lots"
                            searchableColumns={lotsSearchableColumns}
                            debounceMs={300}
                            partialReloadKey="lotsTableData"
                            emptyState={
                                <div className="flex flex-col items-center justify-center gap-4 py-12 text-center">
                                    <div className="rounded-full bg-muted p-4">
                                        <MapPin className="size-8 text-muted-foreground" />
                                    </div>
                                    <div>
                                        <p className="font-medium">
                                            No lots found
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Try adjusting your search or
                                            filters.
                                        </p>
                                    </div>
                                </div>
                            }
                            options={{
                                columnVisibility: true,
                                columnOrdering: true,
                                filters: true,
                                exports: true,
                            }}
                            translations={{
                                noData: 'No lots',
                                search: 'Search lots',
                                clearAllFilters: 'Clear all filters',
                                density: 'Row density',
                                selectAllMatching: (count) =>
                                    `Select all ${count} lots`,
                            }}
                        />
                    </section>
                </div>
            </div>
        </AppSidebarLayout>
    );
}
