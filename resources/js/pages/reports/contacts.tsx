import { Head } from "@inertiajs/react";

interface Props {
    byType: Record<string, number>;
    byStage: Record<string, number>;
    bySource: Record<string, number>;
    recentCount: number;
}

export default function ContactReportPage({
    byType,
    byStage,
    bySource,
    recentCount,
}: Props) {
    return (
        <>
            <Head title="Contact Report" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Contact Report
                    </h1>
                    <p className="text-muted-foreground">
                        {recentCount} contacts added in the last 30 days
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-lg border p-4">
                        <h2 className="mb-3 text-lg font-semibold">By Type</h2>
                        <dl className="space-y-2">
                            {Object.entries(byType).map(([type, count]) => (
                                <div
                                    key={type}
                                    className="flex items-center justify-between"
                                >
                                    <dt className="text-muted-foreground capitalize">
                                        {type ?? "Unknown"}
                                    </dt>
                                    <dd className="font-medium">{count}</dd>
                                </div>
                            ))}
                        </dl>
                    </div>

                    <div className="rounded-lg border p-4">
                        <h2 className="mb-3 text-lg font-semibold">
                            By Stage
                        </h2>
                        <dl className="space-y-2">
                            {Object.entries(byStage).map(([stage, count]) => (
                                <div
                                    key={stage}
                                    className="flex items-center justify-between"
                                >
                                    <dt className="text-muted-foreground capitalize">
                                        {stage ?? "Unknown"}
                                    </dt>
                                    <dd className="font-medium">{count}</dd>
                                </div>
                            ))}
                        </dl>
                    </div>

                    <div className="rounded-lg border p-4">
                        <h2 className="mb-3 text-lg font-semibold">
                            By Source
                        </h2>
                        <dl className="space-y-2">
                            {Object.entries(bySource).map(
                                ([source, count]) => (
                                    <div
                                        key={source}
                                        className="flex items-center justify-between"
                                    >
                                        <dt className="text-muted-foreground capitalize">
                                            {source ?? "Unknown"}
                                        </dt>
                                        <dd className="font-medium">
                                            {count}
                                        </dd>
                                    </div>
                                ),
                            )}
                        </dl>
                    </div>
                </div>
            </div>
        </>
    );
}
