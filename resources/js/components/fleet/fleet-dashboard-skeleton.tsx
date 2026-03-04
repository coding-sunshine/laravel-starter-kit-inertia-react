import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';

/**
 * Skeleton placeholder matching the fleet dashboard layout.
 * Shown while deferred props (chartData) are loading.
 *
 * Sections: health banner, 4 KPI cards, 2 chart areas,
 *           AI panel + fuel trend, 3 operational detail cards.
 */
export function FleetDashboardSkeleton() {
    return (
        <div className="flex flex-col gap-6">
            {/* Health Banner skeleton */}
            <Card>
                <CardContent className="flex items-center gap-6 py-5">
                    <Skeleton className="size-24 shrink-0 rounded-full" />
                    <div className="flex flex-1 flex-col gap-3">
                        <Skeleton className="h-5 w-48" />
                        <Skeleton className="h-4 w-full max-w-md" />
                        <div className="flex gap-4">
                            <Skeleton className="h-4 w-28" />
                            <Skeleton className="h-4 w-28" />
                            <Skeleton className="h-4 w-28" />
                        </div>
                    </div>
                    <Skeleton className="hidden h-9 w-28 rounded-md sm:block" />
                </CardContent>
            </Card>

            {/* 4 KPI Card skeletons */}
            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                {Array.from({ length: 4 }).map((_, i) => (
                    <Card key={i}>
                        <CardContent className="flex flex-col gap-3 py-4">
                            <div className="flex items-center justify-between">
                                <Skeleton className="h-4 w-20" />
                                <Skeleton className="size-5 rounded" />
                            </div>
                            <Skeleton className="h-8 w-16" />
                            <Skeleton className="h-4 w-24" />
                            <Skeleton className="h-12 w-full rounded" />
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* Primary Charts — 4+3 grid */}
            <div className="grid grid-cols-1 gap-4 lg:grid-cols-7">
                <Card className="lg:col-span-4">
                    <CardHeader>
                        <Skeleton className="h-5 w-32" />
                        <Skeleton className="h-3 w-56" />
                    </CardHeader>
                    <CardContent>
                        <Skeleton className="h-[280px] w-full rounded-lg" />
                    </CardContent>
                </Card>
                <Card className="lg:col-span-3">
                    <CardHeader>
                        <Skeleton className="h-5 w-32" />
                        <Skeleton className="h-3 w-48" />
                    </CardHeader>
                    <CardContent>
                        <Skeleton className="h-[280px] w-full rounded-lg" />
                    </CardContent>
                </Card>
            </div>

            {/* AI Intelligence Layer — 4+3 grid */}
            <div className="grid grid-cols-1 gap-4 lg:grid-cols-7">
                <Card className="lg:col-span-4">
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Skeleton className="size-5 rounded" />
                            <Skeleton className="h-5 w-24" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="divide-y divide-border">
                            {Array.from({ length: 5 }).map((_, i) => (
                                <div
                                    key={i}
                                    className="flex items-start gap-3 py-3"
                                >
                                    <Skeleton className="mt-1 size-2.5 shrink-0 rounded-full" />
                                    <div className="flex flex-1 flex-col gap-1.5">
                                        <Skeleton className="h-3 w-24" />
                                        <Skeleton className="h-4 w-full" />
                                    </div>
                                    <Skeleton className="h-7 w-12 shrink-0 rounded" />
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
                <Card className="lg:col-span-3">
                    <CardHeader>
                        <Skeleton className="h-5 w-32" />
                        <Skeleton className="h-3 w-44" />
                    </CardHeader>
                    <CardContent>
                        <Skeleton className="h-[280px] w-full rounded-lg" />
                    </CardContent>
                </Card>
            </div>

            {/* Operational Detail — 3 col */}
            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                {/* Recent Alerts */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Skeleton className="size-4 rounded" />
                            <Skeleton className="h-4 w-24" />
                        </div>
                    </CardHeader>
                    <CardContent className="pt-0">
                        <div className="space-y-2">
                            <Skeleton className="h-4 w-full" />
                            <Skeleton className="h-8 w-28 rounded-md" />
                        </div>
                    </CardContent>
                </Card>

                {/* Upcoming Maintenance */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Skeleton className="size-4 rounded" />
                            <Skeleton className="h-4 w-36" />
                        </div>
                    </CardHeader>
                    <CardContent className="pt-0">
                        <div className="divide-y divide-border">
                            {Array.from({ length: 3 }).map((_, i) => (
                                <div
                                    key={i}
                                    className="flex items-center justify-between py-2"
                                >
                                    <div className="flex flex-col gap-1">
                                        <Skeleton className="h-4 w-28" />
                                        <Skeleton className="h-3 w-20" />
                                    </div>
                                    <Skeleton className="h-3 w-14" />
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Driver Safety Distribution */}
                <Card>
                    <CardHeader>
                        <Skeleton className="h-5 w-24" />
                        <Skeleton className="h-3 w-40" />
                    </CardHeader>
                    <CardContent>
                        <Skeleton className="h-[200px] w-full rounded-lg" />
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
