import Heading from '@/components/heading';
import { LoadingTimesForm } from '@/components/rakes/workflow/LoadingTimesForm';
import { WagonLoadingWorkflow } from '@/components/rakes/workflow/WagonLoadingWorkflow';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import rakeLoader from '@/routes/rake-loader';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

type LoaderOption = { id: number; loader_name: string; code: string };

type Wagon = {
    id: number;
    wagon_number: string;
    wagon_sequence: number;
    wagon_type?: string | null;
    pcc_weight_mt?: string | null;
    is_unfit?: boolean;
};

type WagonLoadingRecord = {
    id?: number;
    wagon_id: number;
    wagon?: {
        wagon_number: string;
        wagon_sequence: number;
        wagon_type?: string | null;
        pcc_weight_mt?: string | null;
    } | null;
    loader_id?: number | null;
    loader?: { loader_name: string; code: string } | null;
    loader_operator_name?: string | null;
    loaded_quantity_mt: string;
    loading_time?: string | null;
    remarks?: string | null;
};

type RakeHydrated = {
    id: number;
    rake_number: string | null;
    loader_weighment_status?: string | null;
    loading_start_time?: string | null;
    loading_end_time?: string | null;
    wagons: Wagon[];
    wagonLoadings?: WagonLoadingRecord[];
    siding?: { id: number; name: string; code: string; loaders?: LoaderOption[] } | null;
    loaderOperatorOptions?: string[];
};

interface Props {
    rake: RakeHydrated;
}

export default function RakeLoaderLoading({ rake: initialRake }: Props) {
    const [rake, setRake] = useState<RakeHydrated>(initialRake);

    const breadcrumbs: BreadcrumbItem[] = useMemo(
        () => [
            { title: 'Rake Loader', href: '/rake-loader' },
            {
                title: rake.rake_number ? `Load ${rake.rake_number}` : 'Wagon loading',
                href: `/rake-loader/rakes/${rake.id}/loading`,
            },
        ],
        [rake.id, rake.rake_number],
    );

    const fitWagons = rake.wagons?.filter((w) => !w.is_unfit) ?? [];
    const positivelyLoadedFitWagonIds = useMemo(() => {
        const ids = new Set<number>();
        for (const l of rake.wagonLoadings ?? []) {
            if (Number(l.loaded_quantity_mt) > 0) {
                ids.add(l.wagon_id);
            }
        }
        return ids;
    }, [rake.wagonLoadings]);
    const allFitWagonsComplete =
        fitWagons.length > 0 && fitWagons.every((w) => positivelyLoadedFitWagonIds.has(w.id));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={rake.rake_number ? `Load ${rake.rake_number}` : 'Wagon loading'} />

            <div className="space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Wagon loading"
                        description={
                            rake.rake_number
                                ? `Loader weighment for rake ${rake.rake_number}.`
                                : 'Enter loader weighment for each wagon.'
                        }
                    />
                    <Button variant="outline" asChild data-pan="rake-loader-back-to-list">
                        <Link href={rakeLoader.index.url()}>Back to list</Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between gap-3">
                            <span>
                                Loader weighment{' '}
                                <span className="text-muted-foreground">
                                    {rake.rake_number ? `(${rake.rake_number})` : ''}
                                </span>
                            </span>
                            <Badge variant={allFitWagonsComplete ? 'default' : 'secondary'}>
                                {allFitWagonsComplete ? 'Ready' : 'In progress'}
                            </Badge>
                        </CardTitle>
                        <CardDescription>
                            Fill loader and loaded quantity for each wagon. Set loading start/end before
                            submitting.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <LoadingTimesForm
                            rakeId={rake.id}
                            loadingStart={rake.loading_start_time ?? null}
                            loadingEnd={rake.loading_end_time ?? null}
                            onTimesSaved={({ loading_start_time, loading_end_time }) =>
                                setRake((prev) => ({
                                    ...prev,
                                    loading_start_time,
                                    loading_end_time,
                                }))
                            }
                        />

                        <WagonLoadingWorkflow
                            rake={{
                                id: rake.id,
                                state: 'loading',
                                loading_start_time: rake.loading_start_time ?? null,
                                loading_end_time: rake.loading_end_time ?? null,
                                wagons: rake.wagons,
                                wagonLoadings: rake.wagonLoadings ?? [],
                                siding: rake.siding ?? null,
                                loaderOperatorOptions: rake.loaderOperatorOptions ?? [],
                            }}
                            disabled={false}
                            compact={false}
                            tableVariant="spreadsheet"
                            onWagonLoadingsSaved={(loadings) =>
                                setRake((prev) => ({ ...prev, wagonLoadings: loadings }))
                            }
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
