import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Filter } from 'lucide-react';

interface FunnelStage {
    label: string;
    count: number;
    key: string;
}

interface Props {
    stages: FunnelStage[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/sales' },
    { title: 'Conversion Funnel', href: '/funnel' },
];

const STAGE_COLORS: Record<string, string> = {
    leads: 'bg-blue-500',
    prospects: 'bg-indigo-500',
    reservations: 'bg-purple-500',
    sales: 'bg-green-500',
};

export default function FunnelIndexPage({ stages }: Props) {
    const maxCount = Math.max(...stages.map((s) => s.count), 1);

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Conversion Funnel" />
            <div
                className="flex h-full flex-1 flex-col gap-6 p-4"
                data-pan="funnel-index"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Conversion Funnel
                    </h1>
                    <p className="text-muted-foreground">
                        Track leads through your sales pipeline
                    </p>
                </div>

                <div className="mx-auto w-full max-w-2xl space-y-4">
                    {stages.map((stage, index) => {
                        const widthPercent =
                            maxCount > 0
                                ? Math.max(
                                      (stage.count / maxCount) * 100,
                                      10,
                                  )
                                : 10;
                        const colorClass =
                            STAGE_COLORS[stage.key] ?? 'bg-gray-500';

                        const conversionRate =
                            index > 0 && stages[index - 1].count > 0
                                ? (
                                      (stage.count /
                                          stages[index - 1].count) *
                                      100
                                  ).toFixed(1)
                                : null;

                        return (
                            <div key={stage.key} className="space-y-1">
                                <div className="flex items-center justify-between text-sm">
                                    <span className="font-medium">
                                        {stage.label}
                                    </span>
                                    <div className="flex items-center gap-3">
                                        {conversionRate !== null && (
                                            <span className="text-xs text-muted-foreground">
                                                {conversionRate}% conversion
                                            </span>
                                        )}
                                        <span className="font-semibold">
                                            {stage.count.toLocaleString()}
                                        </span>
                                    </div>
                                </div>
                                <div className="h-10 w-full overflow-hidden rounded-lg bg-muted">
                                    <div
                                        className={`h-full rounded-lg transition-all duration-500 ${colorClass}`}
                                        style={{ width: `${widthPercent}%` }}
                                    />
                                </div>
                            </div>
                        );
                    })}
                </div>

                {stages.every((s) => s.count === 0) && (
                    <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                        <div className="rounded-full bg-muted p-4">
                            <Filter className="size-8 text-muted-foreground" />
                        </div>
                        <div>
                            <p className="font-medium">No data yet</p>
                            <p className="text-sm text-muted-foreground">
                                Start adding leads and contacts to see your
                                funnel.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppSidebarLayout>
    );
}
