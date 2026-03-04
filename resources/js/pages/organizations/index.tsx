import AppLayout from '@/layouts/app-layout';
import organizations from '@/routes/organizations';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Building2, Plus } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizations', href: organizations.index.url() },
];

interface OrganizationSummary {
    id: number;
    name: string;
    slug: string;
    users_count?: number;
}

interface CurrentOrganization {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    organizations: {
        id: number;
        name: string;
        slug: string;
        users_count?: number;
    }[];
    currentOrganization: CurrentOrganization | null;
}

export default function OrganizationsIndex() {
    const { organizations: userOrganizations, currentOrganization } = usePage<
        Props & SharedData
    >().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organizations" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="heading-4 text-foreground">Organizations</h2>
                    <Button asChild>
                        <Link href={organizations.create.url()}>
                            <Plus className="mr-2 size-4" />
                            New organization
                        </Link>
                    </Button>
                </div>

                {userOrganizations.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Building2 className="size-12 text-muted-foreground" />
                            <p className="mt-4 text-sm text-muted-foreground">
                                You are not in any organizations yet.
                            </p>
                            <Button asChild className="mt-4">
                                <Link href={organizations.create.url()}>
                                    Create one
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {(userOrganizations as OrganizationSummary[]).map(
                            (org) => (
                                <Card key={org.id} className="flex flex-col">
                                    <CardHeader className="pb-2">
                                        <CardTitle className="flex items-center gap-2 text-base">
                                            <Building2 className="size-4 text-muted-foreground" />
                                            {org.name}
                                        </CardTitle>
                                        <CardDescription>
                                            {[
                                                currentOrganization?.id ===
                                                    org.id &&
                                                    'Current organization',
                                                typeof org.users_count ===
                                                    'number' &&
                                                    `${org.users_count} member${org.users_count !== 1 ? 's' : ''}`,
                                            ]
                                                .filter(Boolean)
                                                .join(' • ')}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="mt-auto flex flex-wrap gap-2 pt-2">
                                        <Button
                                            variant="default"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={organizations.show.url({
                                                    organization: org.slug,
                                                })}
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
                                                href={`/organizations/${org.slug}/members`}
                                            >
                                                Members
                                            </Link>
                                        </Button>
                                    </CardContent>
                                </Card>
                            ),
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
