import { Head } from "@inertiajs/react";

interface Props {
    totalSales: number;
    totalCommsIn: number;
    totalCommsOut: number;
    profitMargin: number;
    salesByMonth: Record<string, number>;
    topAgents: Record<string, number>;
}

export default function SalesReportPage({
    totalSales,
    totalCommsIn,
    totalCommsOut,
    profitMargin,
    salesByMonth,
    topAgents,
}: Props) {
    const formatCurrency = (value: number) =>
        new Intl.NumberFormat("en-AU", {
            style: "currency",
            currency: "AUD",
        }).format(value);

    return (
        <>
            <Head title="Sales Report" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Sales Report
                    </h1>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <div className="fusion-card p-4">
                        <p className="text-muted-foreground text-sm">
                            Total Sales
                        </p>
                        <p className="text-3xl font-bold">{totalSales}</p>
                    </div>
                    <div className="fusion-card p-4">
                        <p className="text-muted-foreground text-sm">
                            Total Comms In
                        </p>
                        <p className="text-3xl font-bold">
                            {formatCurrency(totalCommsIn)}
                        </p>
                    </div>
                    <div className="fusion-card p-4">
                        <p className="text-muted-foreground text-sm">
                            Total Comms Out
                        </p>
                        <p className="text-3xl font-bold">
                            {formatCurrency(totalCommsOut)}
                        </p>
                    </div>
                    <div className="fusion-card p-4">
                        <p className="text-muted-foreground text-sm">
                            Profit Margin
                        </p>
                        <p className="text-3xl font-bold">{profitMargin}%</p>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="fusion-card p-4">
                        <h2 className="mb-3 text-lg font-semibold">
                            Sales by Month (Last 6 Months)
                        </h2>
                        <dl className="space-y-2">
                            {Object.entries(salesByMonth).map(
                                ([month, count]) => (
                                    <div
                                        key={month}
                                        className="flex items-center justify-between"
                                    >
                                        <dt className="text-muted-foreground">
                                            {month}
                                        </dt>
                                        <dd className="font-medium">
                                            {count}
                                        </dd>
                                    </div>
                                ),
                            )}
                        </dl>
                    </div>

                    <div className="fusion-card p-4">
                        <h2 className="mb-3 text-lg font-semibold">
                            Top Agents by Commission
                        </h2>
                        <dl className="space-y-2">
                            {Object.entries(topAgents).map(
                                ([agent, commission]) => (
                                    <div
                                        key={agent}
                                        className="flex items-center justify-between"
                                    >
                                        <dt className="text-muted-foreground">
                                            {agent}
                                        </dt>
                                        <dd className="font-medium">
                                            {formatCurrency(commission)}
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
