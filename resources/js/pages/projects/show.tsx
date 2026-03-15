import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Bath,
    Bed,
    Building2,
    Car,
    ChevronLeft,
    ChevronRight,
    Clock,
    DollarSign,
    Flame,
    Layers,
    MapPin,
    Star,
    TrendingUp,
} from 'lucide-react';
import { useState } from 'react';

interface ProjectData {
    id: number;
    slug: string;
    title: string;
    stage: string;
    estate: string | null;
    suburb: string | null;
    state: string | null;
    postcode: string | null;
    description: string | null;
    description_summary: string | null;
    min_price: number | null;
    max_price: number | null;
    avg_price: number | null;
    min_rent: number | null;
    max_rent: number | null;
    rent_yield: number | null;
    total_lots: number | null;
    bedrooms: number | null;
    bathrooms: number | null;
    garage: number | null;
    storeys: number | null;
    build_time: string | null;
    historical_growth: number | null;
    is_hot_property: boolean;
    is_featured: boolean;
    is_archived: boolean;
    developer_name: string | null;
    project_type: string | null;
    property_badges: string[];
    images: string[];
    created_at: string | null;
}

interface LotData {
    id: number;
    lot_number: string | null;
    address: string | null;
    status: string | null;
    price: number | null;
    land_size: number | null;
    bedrooms: number | null;
    bathrooms: number | null;
    garage: number | null;
}

interface ProjectUpdate {
    id: number;
    title: string | null;
    content: string | null;
    created_at: string | null;
}

interface LotStats {
    total: number;
    available: number;
    sold: number;
    reserved: number;
}

interface Props {
    project: ProjectData;
    lots: LotData[];
    updates: ProjectUpdate[];
    lotStats: LotStats;
}

const STAGE_BADGE: Record<string, { label: string; color: 'success' | 'warning' | 'info' | 'error' | 'neutral' }> = {
    active: { label: 'Active', color: 'success' },
    upcoming: { label: 'Upcoming', color: 'info' },
    selling: { label: 'Selling', color: 'success' },
    sold_out: { label: 'Sold Out', color: 'error' },
    completed: { label: 'Completed', color: 'neutral' },
    on_hold: { label: 'On Hold', color: 'warning' },
};

const LOT_STATUS_COLOR: Record<string, 'success' | 'warning' | 'error' | 'neutral'> = {
    available: 'success',
    sold: 'error',
    reserved: 'warning',
};

function formatCurrency(value: number | null): string {
    if (value === null) return '--';
    return new Intl.NumberFormat('en-AU', {
        style: 'currency',
        currency: 'AUD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}

function formatNumber(value: number | null): string {
    if (value === null) return '--';
    return new Intl.NumberFormat('en-AU').format(value);
}

function formatPercent(value: number | null): string {
    if (value === null) return '--';
    return `${value.toFixed(1)}%`;
}

function buildLocation(project: ProjectData): string | null {
    const parts = [project.suburb, project.state, project.postcode].filter(Boolean);
    return parts.length > 0 ? parts.join(', ') : null;
}

function priceRange(project: ProjectData): string {
    if (project.min_price !== null && project.max_price !== null) {
        if (project.min_price === project.max_price) {
            return formatCurrency(project.min_price);
        }
        return `${formatCurrency(project.min_price)} - ${formatCurrency(project.max_price)}`;
    }
    if (project.avg_price !== null) {
        return formatCurrency(project.avg_price);
    }
    return '--';
}

function FallbackImage({ src, alt, className }: { src: string; alt: string; className?: string }) {
    const [failed, setFailed] = useState(false);

    if (failed || !src) {
        return (
            <div className={`flex items-center justify-center bg-muted ${className ?? ''}`}>
                <Building2 className="h-12 w-12 text-muted-foreground/30" />
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={alt}
            className={className}
            onError={() => setFailed(true)}
        />
    );
}

function ImageGallery({ images, title }: { images: string[]; title: string }) {
    const [current, setCurrent] = useState(0);

    if (images.length === 1) {
        return (
            <div className="overflow-hidden rounded-lg">
                <FallbackImage
                    src={images[0]}
                    alt={title}
                    className="h-64 w-full object-cover sm:h-80"
                />
            </div>
        );
    }

    return (
        <div className="relative overflow-hidden rounded-lg">
            <FallbackImage
                src={images[current]}
                alt={`${title} - Photo ${current + 1}`}
                className="h-64 w-full object-cover sm:h-80"
            />
            <div className="absolute inset-y-0 left-0 flex items-center">
                <Button
                    variant="ghost"
                    size="icon"
                    className="ml-2 bg-black/30 text-white hover:bg-black/50"
                    onClick={() => setCurrent((p) => (p === 0 ? images.length - 1 : p - 1))}
                    aria-label="Previous photo"
                >
                    <ChevronLeft className="h-5 w-5" />
                </Button>
            </div>
            <div className="absolute inset-y-0 right-0 flex items-center">
                <Button
                    variant="ghost"
                    size="icon"
                    className="mr-2 bg-black/30 text-white hover:bg-black/50"
                    onClick={() => setCurrent((p) => (p === images.length - 1 ? 0 : p + 1))}
                    aria-label="Next photo"
                >
                    <ChevronRight className="h-5 w-5" />
                </Button>
            </div>
            <div className="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5">
                {images.map((_, i) => (
                    <button
                        key={i}
                        className={`h-2 w-2 rounded-full transition-colors ${i === current ? 'bg-white' : 'bg-white/50'}`}
                        onClick={() => setCurrent(i)}
                        aria-label={`Go to photo ${i + 1}`}
                    />
                ))}
            </div>
        </div>
    );
}

export default function ProjectShowPage({ project, lots, updates, lotStats }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Properties', href: '/projects' },
        { title: 'Projects', href: '/projects' },
        { title: project.title, href: `/projects/${project.slug}` },
    ];

    const stage = STAGE_BADGE[project.stage] ?? {
        label: project.stage,
        color: 'neutral' as const,
    };

    const location = buildLocation(project);

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title={project.title} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6" data-pan="project-detail">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="flex items-start gap-3">
                        <Button variant="ghost" size="icon" asChild className="mt-0.5">
                            <Link href="/projects" aria-label="Back to projects">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div className="space-y-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <h1 className="text-xl font-semibold tracking-tight">
                                    {project.title}
                                </h1>
                                <Badge variant="filled" color={stage.color}>
                                    {stage.label}
                                </Badge>
                                {project.is_hot_property && (
                                    <Badge variant="soft" color="error">
                                        <Flame className="h-3 w-3" />
                                        Hot Property
                                    </Badge>
                                )}
                                {project.is_featured && (
                                    <Badge variant="soft" color="warning">
                                        <Star className="h-3 w-3" />
                                        Featured
                                    </Badge>
                                )}
                                {project.is_archived && (
                                    <Badge variant="soft" color="neutral">
                                        Archived
                                    </Badge>
                                )}
                            </div>
                            <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                {location && (
                                    <span className="flex items-center gap-1">
                                        <MapPin className="h-3.5 w-3.5" />
                                        {location}
                                    </span>
                                )}
                                {project.developer_name && (
                                    <span className="flex items-center gap-1">
                                        <Building2 className="h-3.5 w-3.5" />
                                        {project.developer_name}
                                    </span>
                                )}
                                {project.project_type && (
                                    <span className="flex items-center gap-1">
                                        <Layers className="h-3.5 w-3.5" />
                                        {project.project_type}
                                    </span>
                                )}
                            </div>
                            {project.property_badges.length > 0 && (
                                <div className="flex flex-wrap gap-1.5 pt-1">
                                    {project.property_badges.map((badge) => (
                                        <Badge key={badge} variant="outline" color="info">
                                            {badge}
                                        </Badge>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Image Gallery */}
                {project.images.length > 0 && (
                    <ImageGallery images={project.images} title={project.title} />
                )}

                <Separator />

                {/* Tabs */}
                <Tabs defaultValue="overview">
                    <TabsList variant="line">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="lots">
                            Lots
                            {lotStats.total > 0 && (
                                <Badge variant="soft" color="neutral" className="ml-1.5">
                                    {lotStats.total}
                                </Badge>
                            )}
                        </TabsTrigger>
                        <TabsTrigger value="activity">
                            Activity
                            {updates.length > 0 && (
                                <Badge variant="soft" color="neutral" className="ml-1.5">
                                    {updates.length}
                                </Badge>
                            )}
                        </TabsTrigger>
                    </TabsList>

                    {/* Overview Tab */}
                    <TabsContent value="overview" className="space-y-6 pt-4">
                        {/* Key Metrics */}
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                        <Layers className="h-3.5 w-3.5" />
                                        Total Lots
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold">
                                        {formatNumber(project.total_lots)}
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                        <Building2 className="h-3.5 w-3.5" />
                                        Available
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold">
                                        {formatNumber(lotStats.available)}
                                    </p>
                                    {lotStats.total > 0 && (
                                        <p className="text-xs text-muted-foreground">
                                            of {formatNumber(lotStats.total)} total
                                        </p>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                        <DollarSign className="h-3.5 w-3.5" />
                                        Price Range
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold">
                                        {priceRange(project)}
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                        <TrendingUp className="h-3.5 w-3.5" />
                                        Rental Yield
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold">
                                        {formatPercent(project.rent_yield)}
                                    </p>
                                    {project.min_rent !== null && project.max_rent !== null && (
                                        <p className="text-xs text-muted-foreground">
                                            {formatCurrency(project.min_rent)} - {formatCurrency(project.max_rent)} /wk
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        {/* Description */}
                        {(project.description_summary || project.description) && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm">Description</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm leading-relaxed text-muted-foreground">
                                        {project.description_summary || project.description}
                                    </p>
                                </CardContent>
                            </Card>
                        )}

                        {/* Property Details */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">Property Details</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <dl className="space-y-1">
                                        <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                            <Bed className="h-3.5 w-3.5" />
                                            Bedrooms
                                        </dt>
                                        <dd className="text-sm">
                                            {project.bedrooms !== null ? project.bedrooms : '--'}
                                        </dd>
                                    </dl>

                                    <dl className="space-y-1">
                                        <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                            <Bath className="h-3.5 w-3.5" />
                                            Bathrooms
                                        </dt>
                                        <dd className="text-sm">
                                            {project.bathrooms !== null ? project.bathrooms : '--'}
                                        </dd>
                                    </dl>

                                    <dl className="space-y-1">
                                        <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                            <Car className="h-3.5 w-3.5" />
                                            Garage
                                        </dt>
                                        <dd className="text-sm">
                                            {project.garage !== null ? project.garage : '--'}
                                        </dd>
                                    </dl>

                                    <dl className="space-y-1">
                                        <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                            <Layers className="h-3.5 w-3.5" />
                                            Storeys
                                        </dt>
                                        <dd className="text-sm">
                                            {project.storeys !== null ? project.storeys : '--'}
                                        </dd>
                                    </dl>

                                    <dl className="space-y-1">
                                        <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                            <Clock className="h-3.5 w-3.5" />
                                            Build Time
                                        </dt>
                                        <dd className="text-sm">
                                            {project.build_time ?? '--'}
                                        </dd>
                                    </dl>

                                    <dl className="space-y-1">
                                        <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                            <TrendingUp className="h-3.5 w-3.5" />
                                            Historical Growth
                                        </dt>
                                        <dd className="text-sm">
                                            {formatPercent(project.historical_growth)}
                                        </dd>
                                    </dl>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Lots Tab */}
                    <TabsContent value="lots" className="pt-4">
                        {lots.length === 0 ? (
                            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center">
                                <Layers className="h-8 w-8 text-muted-foreground/50" />
                                <p className="mt-2 text-sm font-medium text-muted-foreground">
                                    No lots available
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto rounded-lg border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">Lot #</th>
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">Address</th>
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
                                            <th className="px-4 py-3 text-right font-medium text-muted-foreground">Price</th>
                                            <th className="px-4 py-3 text-right font-medium text-muted-foreground">Land Size</th>
                                            <th className="px-4 py-3 text-center font-medium text-muted-foreground">Bed</th>
                                            <th className="px-4 py-3 text-center font-medium text-muted-foreground">Bath</th>
                                            <th className="px-4 py-3 text-center font-medium text-muted-foreground">Garage</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {lots.map((lot) => (
                                            <tr key={lot.id} className="hover:bg-muted/30 transition-colors">
                                                <td className="px-4 py-3 font-medium">
                                                    {lot.lot_number ?? '--'}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {lot.address ?? '--'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {lot.status ? (
                                                        <Badge
                                                            variant="soft"
                                                            color={LOT_STATUS_COLOR[lot.status.toLowerCase()] ?? 'neutral'}
                                                        >
                                                            {lot.status}
                                                        </Badge>
                                                    ) : (
                                                        '--'
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {formatCurrency(lot.price)}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {lot.land_size !== null ? `${formatNumber(lot.land_size)} m\u00B2` : '--'}
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    {lot.bedrooms ?? '--'}
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    {lot.bathrooms ?? '--'}
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    {lot.garage ?? '--'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {/* Lot summary bar */}
                        {lotStats.total > 0 && (
                            <div className="mt-4 flex flex-wrap gap-4 text-sm text-muted-foreground">
                                <span>
                                    <span className="font-medium text-foreground">{lotStats.available}</span> Available
                                </span>
                                <span>
                                    <span className="font-medium text-foreground">{lotStats.reserved}</span> Reserved
                                </span>
                                <span>
                                    <span className="font-medium text-foreground">{lotStats.sold}</span> Sold
                                </span>
                            </div>
                        )}
                    </TabsContent>

                    {/* Activity Tab */}
                    <TabsContent value="activity" className="pt-4">
                        {updates.length === 0 ? (
                            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center">
                                <Clock className="h-8 w-8 text-muted-foreground/50" />
                                <p className="mt-2 text-sm font-medium text-muted-foreground">
                                    No updates yet
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {updates.map((update) => (
                                    <Card key={update.id}>
                                        <CardHeader>
                                            <div className="flex items-center justify-between">
                                                <CardTitle className="text-sm">
                                                    {update.title ?? 'Update'}
                                                </CardTitle>
                                                {update.created_at && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {new Date(update.created_at).toLocaleDateString(undefined, {
                                                            year: 'numeric',
                                                            month: 'short',
                                                            day: 'numeric',
                                                        })}
                                                    </span>
                                                )}
                                            </div>
                                        </CardHeader>
                                        {update.content && (
                                            <CardContent>
                                                <p className="text-sm leading-relaxed text-muted-foreground">
                                                    {update.content}
                                                </p>
                                            </CardContent>
                                        )}
                                    </Card>
                                ))}
                            </div>
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </AppSidebarLayout>
    );
}
