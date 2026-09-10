import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';
import {
    ChevronDown,
    ChevronUp,
    FileJson,
    LayoutGrid,
    ListChecks,
    Scale,
    Train,
} from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { ChargesTable } from './ChargesTable';
import { PenaltiesTable } from './PenaltiesTable';
import type {
    ChargeRow,
    OverviewData,
    PenaltyRow,
    WagonRow,
} from './types';
import { WagonTable } from './WagonTable';

export type TabValue = 'overview' | 'wagons' | 'charges' | 'penalties' | 'raw';

export type { OverviewData, WagonRow, ChargeRow, PenaltyRow };

export interface RRTabsProps {
    overviewData: OverviewData;
    wagons: WagonRow[];
    charges: ChargeRow[];
    penalties: PenaltyRow[];
    rawData: Record<string, unknown>;
}

const tabs: { value: TabValue; label: string; icon: typeof LayoutGrid }[] = [
    { value: 'overview', label: 'Overview', icon: LayoutGrid },
    { value: 'wagons', label: 'Wagons', icon: Train },
    { value: 'charges', label: 'Charges', icon: ListChecks },
    { value: 'penalties', label: 'Penalties', icon: Scale },
    { value: 'raw', label: 'Raw RR Data', icon: FileJson },
];

export function RRTabs({
    overviewData,
    wagons,
    charges,
    penalties,
    rawData,
}: RRTabsProps) {
    const page = usePage<{ auth?: { roles?: string[] } }>();
    const roles = page.props.auth?.roles ?? [];
    const isSuperAdmin = roles.includes('super-admin') || roles.includes('super_admin');

    const [activeTab, setActiveTab] = useState<TabValue>('overview');
    const [rawExpanded, setRawExpanded] = useState(false);

    const visibleTabs = useMemo(() => {
        if (isSuperAdmin) {
            return tabs;
        }

        return tabs.filter((t) => t.value !== 'raw');
    }, [isSuperAdmin]);

    useEffect(() => {
        if (!isSuperAdmin && activeTab === 'raw') {
            setActiveTab('overview');
        }
    }, [activeTab, isSuperAdmin]);

    return (
        <div className="space-y-6">
            <ToggleGroup
                type="single"
                value={activeTab}
                onValueChange={(v) => v && setActiveTab(v as TabValue)}
                className={cn(
                    'flex flex-wrap gap-1 rounded-lg bg-muted/50 p-1',
                )}
                data-pan="rr-details-tabs"
            >
                {visibleTabs.map(({ value, label, icon: Icon }) => (
                    <ToggleGroupItem
                        key={value}
                        value={value}
                        aria-label={label}
                        data-pan={`rr-tab-${value}`}
                        className={cn(
                            'flex items-center gap-2 rounded-md px-4 py-2 text-sm transition-colors',
                            activeTab === value
                                ? 'bg-background shadow-sm'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        )}
                    >
                        <Icon className="size-4" />
                        {label}
                    </ToggleGroupItem>
                ))}
            </ToggleGroup>

            <div className="min-h-[200px]">
                {activeTab === 'overview' && (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>RR Number</CardDescription>
                                <CardTitle className="text-base">
                                    {overviewData.rrNumber}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>Rake Number</CardDescription>
                                <CardTitle className="text-base">
                                    {overviewData.rakeNumber}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>FNR</CardDescription>
                                <CardTitle className="text-base">
                                    {overviewData.fnr}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>From Station / Siding</CardDescription>
                                <CardTitle className="text-base">
                                    {overviewData.fromStation}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>To Station / Power Plant</CardDescription>
                                <CardTitle className="text-base">
                                    {overviewData.toStation}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>Distance (KM)</CardDescription>
                                <CardTitle className="text-base">
                                    {overviewData.distanceKm}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>Commodity</CardDescription>
                                <CardTitle className="text-base">
                                    {overviewData.commodity}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>Total Wagons</CardDescription>
                                <CardTitle className="text-base">
                                    {overviewData.totalWagons}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>Total Weight</CardDescription>
                                <CardTitle className="text-base">
                                    {overviewData.totalWeight}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>Freight Total</CardDescription>
                                <CardTitle className="text-base">
                                    {overviewData.freightTotal}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>Rate</CardDescription>
                                <CardTitle className="text-base">
                                    {overviewData.rate}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>Class</CardDescription>
                                <CardTitle className="text-base">
                                    {overviewData.class}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                    </div>
                )}

                {activeTab === 'wagons' && (
                    <WagonTable data={wagons} />
                )}

                {activeTab === 'charges' && (
                    <ChargesTable data={charges} />
                )}

                {activeTab === 'penalties' && (
                    <PenaltiesTable data={penalties} />
                )}

                {isSuperAdmin && activeTab === 'raw' && (
                    <Collapsible
                        open={rawExpanded}
                        onOpenChange={setRawExpanded}
                    >
                        <CollapsibleTrigger asChild>
                            <button
                                type="button"
                                className="flex w-full items-center justify-between rounded-lg border bg-muted/30 px-4 py-3 text-left font-medium transition-colors hover:bg-muted/50"
                            >
                                Parsed RR Data
                                {rawExpanded ? (
                                    <ChevronUp className="size-4" />
                                ) : (
                                    <ChevronDown className="size-4" />
                                )}
                            </button>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <pre className="mt-4 overflow-x-auto rounded-lg border bg-muted/30 p-4 text-xs">
                                {JSON.stringify(rawData, null, 2)}
                            </pre>
                        </CollapsibleContent>
                    </Collapsible>
                )}
            </div>
        </div>
    );
}
