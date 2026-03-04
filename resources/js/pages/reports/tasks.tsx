import { Head } from "@inertiajs/react";

interface Props {
    byStatus: Record<string, number>;
    overdueCount: number;
    completionRate: number;
    createdThisMonth: number;
}

export default function TaskReportPage({
    byStatus,
    overdueCount,
    completionRate,
    createdThisMonth,
}: Props) {
    return (
        <>
            <Head title="Task Report" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Task Report
                    </h1>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="fusion-card p-4">
                        <p className="text-muted-foreground text-sm">
                            Completion Rate
                        </p>
                        <p className="text-3xl font-bold">{completionRate}%</p>
                    </div>
                    <div className="fusion-card p-4">
                        <p className="text-muted-foreground text-sm">
                            Overdue Tasks
                        </p>
                        <p className="text-3xl font-bold">{overdueCount}</p>
                    </div>
                    <div className="fusion-card p-4">
                        <p className="text-muted-foreground text-sm">
                            Created This Month
                        </p>
                        <p className="text-3xl font-bold">
                            {createdThisMonth}
                        </p>
                    </div>
                </div>

                <div className="fusion-card p-4">
                    <h2 className="mb-3 text-lg font-semibold">By Status</h2>
                    <dl className="space-y-2">
                        {Object.entries(byStatus).map(([status, count]) => (
                            <div
                                key={status}
                                className="flex items-center justify-between"
                            >
                                <dt className="text-muted-foreground capitalize">
                                    {status ?? "Unknown"}
                                </dt>
                                <dd className="font-medium">{count}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </div>
        </>
    );
}
