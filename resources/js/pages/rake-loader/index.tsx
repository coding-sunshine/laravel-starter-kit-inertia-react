import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { LoadingTimesForm } from '@/components/rakes/workflow/LoadingTimesForm';
import { WagonLoadingWorkflow } from '@/components/rakes/workflow/WagonLoadingWorkflow';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

type SidingOption = { id: number; name: string; code: string };
type RakeOption = { id: number; rake_number: string | null };

type Wagon = {
    id: number;
    wagon_number: string;
    wagon_sequence: number;
    wagon_type?: string | null;
    pcc_weight_mt?: string | null;
    is_unfit?: boolean;
};

type LoaderOption = { id: number; loader_name: string; code: string };

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
};

interface Props {
    defaultDate: string;
    sidings: SidingOption[];
    defaultSidingId: number | null;
    isSuperAdmin: boolean;
}

export default function RakeLoaderIndex({
    defaultDate,
    sidings,
    defaultSidingId,
    isSuperAdmin,
}: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Rake Loader', href: '/rake-loader' },
    ];

    const [date, setDate] = useState(defaultDate);
    const [sidingId, setSidingId] = useState<string>(defaultSidingId ? String(defaultSidingId) : '');

    const [rakes, setRakes] = useState<RakeOption[]>([]);
    const [selectedRakeId, setSelectedRakeId] = useState<string>('');

    const [rakesLoading, setRakesLoading] = useState(false);
    const [rakeLoading, setRakeLoading] = useState(false);
    const [validationError, setValidationError] = useState<string | null>(null);

    const [rake, setRake] = useState<RakeHydrated | null>(null);
    const selectedRakeLabel = useMemo(() => {
        if (!selectedRakeId) {
            return null;
        }
        const match = rakes.find((r) => String(r.id) === String(selectedRakeId));
        return match?.rake_number ?? `Rake #${selectedRakeId}`;
    }, [rakes, selectedRakeId]);

    const canQueryRakes = useMemo(() => {
        if (!date) return false;
        if (isSuperAdmin) return Boolean(sidingId);
        return true;
    }, [date, isSuperAdmin, sidingId]);

    useEffect(() => {
        setSelectedRakeId('');
        setRake(null);
        setValidationError(null);
    }, [date, sidingId]);

    useEffect(() => {
        if (!canQueryRakes) {
            setRakes([]);
            return;
        }

        let cancelled = false;
        setRakesLoading(true);
        setValidationError(null);

        void (async () => {
            try {
                const params = new URLSearchParams();
                params.set('date', date);
                if (isSuperAdmin && sidingId) {
                    params.set('siding_id', sidingId);
                }

                const response = await fetch(`/rake-loader/rakes?${params.toString()}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const data = (await response.json().catch(() => null)) as
                    | { rakes?: RakeOption[]; message?: string }
                    | null;

                if (cancelled) return;

                if (!response.ok) {
                    setValidationError(data?.message ?? 'Failed to load rakes.');
                    setRakes([]);
                    return;
                }

                setRakes(Array.isArray(data?.rakes) ? data!.rakes! : []);
            } catch {
                if (!cancelled) {
                    setValidationError('Failed to load rakes.');
                    setRakes([]);
                }
            } finally {
                if (!cancelled) {
                    setRakesLoading(false);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [canQueryRakes, date, isSuperAdmin, sidingId]);

    useEffect(() => {
        if (!selectedRakeId) {
            setRake(null);
            setRakeLoading(false);
            return;
        }

        let cancelled = false;
        setRake(null);
        setRakeLoading(true);
        setValidationError(null);

        void (async () => {
            try {
                const response = await fetch(`/rake-loader/rakes/${selectedRakeId}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const data = (await response.json().catch(() => null)) as
                    | { rake?: RakeHydrated; message?: string }
                    | null;

                if (cancelled) return;

                if (!response.ok) {
                    setRake(null);
                    setValidationError(data?.message ?? 'Failed to load rake.');
                    return;
                }

                setRake(data?.rake ?? null);
            } catch {
                if (!cancelled) {
                    setRake(null);
                    setValidationError('Failed to load rake.');
                }
            } finally {
                if (!cancelled) {
                    setRakeLoading(false);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [selectedRakeId]);

    const fitWagons = rake?.wagons?.filter((w) => !w.is_unfit) ?? [];
    const positivelyLoadedFitWagonIds = useMemo(() => {
        const ids = new Set<number>();
        for (const l of rake?.wagonLoadings ?? []) {
            if (Number(l.loaded_quantity_mt) > 0) {
                ids.add(l.wagon_id);
            }
        }
        return ids;
    }, [rake?.wagonLoadings]);
    const allFitWagonsComplete =
        fitWagons.length > 0 && fitWagons.every((w) => positivelyLoadedFitWagonIds.has(w.id));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rake Loader" />

            <div className="space-y-6">
                <Heading
                    title="Rake Loader"
                    description="Select a date and rake number, then enter loader and loader weight for each wagon."
                />

                {selectedRakeLabel && (
                    <Card>
                        <CardHeader className="py-4">
                            <CardTitle className="text-xl md:text-2xl">
                                Loading data entry for{' '}
                                <span className="font-black">{selectedRakeLabel}</span>
                            </CardTitle>
                            <CardDescription className="text-sm md:text-base">
                                You are entering loader and loader weighment for this rake.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                )}

                {validationError && (
                    <Alert variant="destructive">
                        <AlertTitle>Validation</AlertTitle>
                        <AlertDescription>{validationError}</AlertDescription>
                    </Alert>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Selection</CardTitle>
                        <CardDescription>
                            Choose date, siding (super-admin), and rake number.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-3">
                        <div className="space-y-2">
                            <Label htmlFor="loading_date">Date</Label>
                            <Input
                                id="loading_date"
                                type="date"
                                value={date}
                                onChange={(e) => setDate(e.target.value)}
                            />
                            <InputError message={errors?.date} />
                        </div>

                        <div className="space-y-2">
                            <Label>Siding</Label>
                            {isSuperAdmin ? (
                                <Select value={sidingId} onValueChange={setSidingId}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select siding" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {sidings.map((s) => (
                                            <SelectItem key={s.id} value={String(s.id)}>
                                                {s.name} ({s.code})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            ) : (
                                <div className="rounded-md border px-3 py-2 text-sm text-muted-foreground">
                                    Current siding
                                </div>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label>Rake number</Label>
                            <Select
                                value={selectedRakeId}
                                onValueChange={setSelectedRakeId}
                                disabled={!canQueryRakes || rakesLoading}
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder={
                                            rakesLoading
                                                ? 'Loading rakes…'
                                                : 'Select rake'
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {rakes.length === 0 ? (
                                        <SelectItem value="__none" disabled>
                                            No rakes found
                                        </SelectItem>
                                    ) : (
                                        rakes.map((r) => (
                                            <SelectItem key={r.id} value={String(r.id)}>
                                                {r.rake_number ?? `Rake #${r.id}`}
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                {selectedRakeId && rakeLoading && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-xl md:text-2xl">
                                Loading {selectedRakeLabel ?? 'rake'}…
                            </CardTitle>
                            <CardDescription className="text-sm md:text-base">
                                Fetching wagons and loader weighment rows.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-center py-10">
                                <div className="flex flex-col items-center gap-4">
                                    <Loader2 className="h-12 w-12 animate-spin text-muted-foreground" />
                                    <div className="text-center">
                                        <div className="text-base font-semibold">
                                            Please wait
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            This may take a few seconds.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {!rakeLoading && rake && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between gap-3">
                                <span>
                                    Loader Weighment{' '}
                                    <span className="text-muted-foreground">
                                        {rake.rake_number ? `(${rake.rake_number})` : ''}
                                    </span>
                                </span>
                                <Badge variant={allFitWagonsComplete ? 'default' : 'secondary'}>
                                    {allFitWagonsComplete ? 'Ready' : 'In progress'}
                                </Badge>
                            </CardTitle>
                            <CardDescription>
                                Fill loader and loaded quantity for each wagon. Make sure loading
                                start/end are set before submitting.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <LoadingTimesForm
                                rakeId={rake.id}
                                loadingStart={rake.loading_start_time ?? null}
                                loadingEnd={rake.loading_end_time ?? null}
                                onTimesSaved={({ loading_start_time, loading_end_time }) =>
                                    setRake((prev) =>
                                        prev
                                            ? {
                                                  ...prev,
                                                  loading_start_time,
                                                  loading_end_time,
                                              }
                                            : prev,
                                    )
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
                                }}
                                disabled={false}
                                compact={false}
                                onWagonLoadingsSaved={(loadings) =>
                                    setRake((prev) =>
                                        prev ? { ...prev, wagonLoadings: loadings } : prev,
                                    )
                                }
                            />
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

