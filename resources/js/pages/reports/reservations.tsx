import { Head } from "@inertiajs/react";

interface Props {
    totalReservations: number;
    thisMonth: number;
    byProject: Record<string, number>;
    averagePurchasePrice: number;
}

export default function ReservationReportPage({
    totalReservations,
    thisMonth,
    byProject,
    averagePurchasePrice,
}: Props) {
    const formatCurrency = (value: number) =>
        new Intl.NumberFormat("en-AU", {
            style: "currency",
            currency: "AUD",
        }).format(value);

    return (
        <>
            <Head title="Reservation Report" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Reservation Report
                    </h1>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="fusion-card p-4">
                        <p className="text-muted-foreground text-sm">
                            Total Reservations
                        </p>
                        <p className="text-3xl font-bold">
                            {totalReservations}
                        </p>
                    </div>
                    <div className="fusion-card p-4">
                        <p className="text-muted-foreground text-sm">
                            This Month
                        </p>
                        <p className="text-3xl font-bold">{thisMonth}</p>
                    </div>
                    <div className="fusion-card p-4">
                        <p className="text-muted-foreground text-sm">
                            Avg. Purchase Price
                        </p>
                        <p className="text-3xl font-bold">
                            {formatCurrency(averagePurchasePrice)}
                        </p>
                    </div>
                </div>

                <div className="fusion-card p-4">
                    <h2 className="mb-3 text-lg font-semibold">
                        By Project (Top 10)
                    </h2>
                    <dl className="space-y-2">
                        {Object.entries(byProject).map(([project, count]) => (
                            <div
                                key={project}
                                className="flex items-center justify-between"
                            >
                                <dt className="text-muted-foreground">
                                    {project}
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
