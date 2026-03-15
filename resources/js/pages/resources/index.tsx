import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { BookOpen, ExternalLink } from 'lucide-react';

interface ResourceItem {
    id: number;
    title: string;
    category: string;
    description: string | null;
    url: string | null;
    type: string;
    created_at: string;
}

interface Props {
    resourcesByCategory: Record<string, ResourceItem[]>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Resources', href: '/resources' },
];

export default function ResourcesIndexPage({
    resourcesByCategory,
}: Props) {
    const categories = Object.keys(resourcesByCategory);
    const totalResources = Object.values(resourcesByCategory).reduce(
        (sum, items) => sum + items.length,
        0,
    );

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Resources" />
            <div
                className="flex h-full flex-1 flex-col gap-6 p-4"
                data-pan="resource-library-page"
            >
                <div>
                    <div className="flex items-center gap-2">
                        <BookOpen className="h-5 w-5 text-muted-foreground" />
                        <h1 className="text-2xl font-bold tracking-tight">
                            Resources
                        </h1>
                    </div>
                    <p className="text-muted-foreground">
                        {totalResources}{' '}
                        {totalResources === 1 ? 'resource' : 'resources'}{' '}
                        across {categories.length}{' '}
                        {categories.length === 1 ? 'category' : 'categories'}
                    </p>
                </div>

                {categories.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                        <div className="rounded-full bg-muted p-4">
                            <BookOpen className="size-8 text-muted-foreground" />
                        </div>
                        <div>
                            <p className="font-medium">No resources available</p>
                            <p className="text-sm text-muted-foreground">
                                Resources will appear here once they are added.
                            </p>
                        </div>
                    </div>
                ) : (
                    categories.map((category) => (
                        <section key={category}>
                            <h2 className="mb-3 text-lg font-semibold tracking-tight">
                                {category}
                            </h2>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {resourcesByCategory[category].map(
                                    (resource) => (
                                        <Card key={resource.id}>
                                            <CardHeader>
                                                <CardTitle className="text-base">
                                                    {resource.title}
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent className="space-y-3">
                                                {resource.description && (
                                                    <p className="text-sm text-muted-foreground">
                                                        {resource.description}
                                                    </p>
                                                )}
                                                {resource.url && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <a
                                                            href={resource.url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                        >
                                                            <ExternalLink className="mr-1.5 h-3.5 w-3.5" />
                                                            Open
                                                        </a>
                                                    </Button>
                                                )}
                                            </CardContent>
                                        </Card>
                                    ),
                                )}
                            </div>
                        </section>
                    ))
                )}
            </div>
        </AppSidebarLayout>
    );
}
