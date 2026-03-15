import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    Building2,
    DollarSign,
    Flame,
    Layers,
    MapPin,
    Star,
} from 'lucide-react';

interface ProjectCard {
    id: number;
    slug: string;
    title: string;
    stage: string;
    suburb: string;
    state: string;
    developer_name: string;
    min_price: number | null;
    max_price: number | null;
    total_lots: number | null;
    is_hot_property: boolean;
    is_featured: boolean;
}

interface Props {
    projects: ProjectCard[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Properties', href: '/projects' },
    { title: 'Favourites', href: '/favourites' },
];

function formatPrice(value: number | null): string {
    if (value === null) return '—';
    return new Intl.NumberFormat('en-AU', {
        style: 'currency',
        currency: 'AUD',
        maximumFractionDigits: 0,
    }).format(value);
}

function handleUnfavourite(
    event: React.MouseEvent,
    projectId: number,
): void {
    event.preventDefault();
    event.stopPropagation();
    router.post(
        '/favourites/toggle',
        { project_id: projectId },
        { preserveScroll: true },
    );
}

export default function FavouritesIndexPage({ projects }: Props) {
    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Favourites" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="favourites-page"
            >
                <div>
                    <div className="flex items-center gap-2">
                        <Star className="h-5 w-5 text-amber-500" />
                        <h1 className="text-2xl font-bold tracking-tight">
                            Favourites
                        </h1>
                    </div>
                    <p className="text-muted-foreground">
                        {projects.length}{' '}
                        {projects.length === 1
                            ? 'favourited project'
                            : 'favourited projects'}
                    </p>
                </div>

                {projects.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                        <div className="rounded-full bg-muted p-4">
                            <Star className="size-8 text-muted-foreground" />
                        </div>
                        <div>
                            <p className="font-medium">No favourites yet</p>
                            <p className="text-sm text-muted-foreground">
                                Browse projects and add them to your favourites.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {projects.map((project) => (
                            <Link
                                key={project.id}
                                href={`/projects/${project.slug}`}
                                className="block"
                            >
                                <Card className="h-full transition-colors hover:bg-accent/50">
                                    <CardHeader>
                                        <div className="flex items-start justify-between gap-2">
                                            <CardTitle className="text-base">
                                                {project.title}
                                            </CardTitle>
                                            <div className="flex shrink-0 items-center gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-7 w-7"
                                                    onClick={(e) =>
                                                        handleUnfavourite(
                                                            e,
                                                            project.id,
                                                        )
                                                    }
                                                    title="Remove from favourites"
                                                >
                                                    <Star className="h-4 w-4 fill-amber-500 text-amber-500" />
                                                </Button>
                                                {project.is_hot_property && (
                                                    <Flame className="h-4 w-4 text-orange-500" />
                                                )}
                                                <Badge variant="outline">
                                                    {project.stage}
                                                </Badge>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-2 text-sm">
                                        <div className="flex items-center gap-1.5 text-muted-foreground">
                                            <MapPin className="h-3.5 w-3.5 shrink-0" />
                                            <span>
                                                {project.suburb},{' '}
                                                {project.state}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-1.5 text-muted-foreground">
                                            <Building2 className="h-3.5 w-3.5 shrink-0" />
                                            <span>
                                                {project.developer_name}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-1.5 text-muted-foreground">
                                            <DollarSign className="h-3.5 w-3.5 shrink-0" />
                                            <span>
                                                {formatPrice(
                                                    project.min_price,
                                                )}{' '}
                                                &ndash;{' '}
                                                {formatPrice(
                                                    project.max_price,
                                                )}
                                            </span>
                                        </div>
                                        {project.total_lots !== null && (
                                            <div className="flex items-center gap-1.5 text-muted-foreground">
                                                <Layers className="h-3.5 w-3.5 shrink-0" />
                                                <span>
                                                    {project.total_lots} lots
                                                </span>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppSidebarLayout>
    );
}
