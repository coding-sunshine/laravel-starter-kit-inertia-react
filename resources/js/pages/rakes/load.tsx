import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ChevronDown, ChevronRight, Clock, Package, ShieldCheck, Train } from 'lucide-react';
import { useRakeLoadBroadcasting } from '@/hooks/use-rake-load-broadcasting';

interface Siding {
    id: number;
    name: string;
    code: string;
    loaders?: { id: number; loader_name: string; code: string }[];
}

interface Wagon {
    id: number;
    wagon_sequence: number;
    wagon_number: string;
    tare_weight_mt: string | null;
    pcc_weight_mt: string | null;
    is_unfit: boolean;
}

interface TxrRecord {
    id: number;
    status: string;
}

interface RakeLoad {
    id: number;
    placement_time: string;
    free_time_minutes: number;
    status: string;
    wagon_loadings?: RakeWagonLoading[];
    wagonLoadings?: RakeWagonLoading[];
    guard_inspections?: GuardInspectionRecord[];
    guardInspections?: GuardInspectionRecord[];
    weighments?: WeighmentRecord[];
}

interface RakeWagonLoading {
    id: number;
    wagon_id: number;
    loader_id: number | null;
    loaded_quantity_mt: string;
    attempt_no: number;
    wagon?: {
        id: number;
        wagon_number: string;
        wagon_sequence: number;
        tare_weight_mt: string | null;
        pcc_weight_mt: string | null;
    };
    loader?: {
        id: number;
        loader_name: string;
        code: string;
    };
}

interface GuardInspectionRecord {
    id: number;
    inspection_time: string;
    movement_permission_time: string | null;
    is_approved: boolean;
    remarks: string | null;
}

interface RakeWagonWeighment {
    id: number;
    wagon_id: number;
    gross_weight_mt: string;
    is_overloaded: boolean;
}

interface WeighmentRecord {
    id: number;
    weighment_time: string;
    total_weight_mt: string;
    status: string | null;
    train_speed_kmph: number;
    attempt_no: number;
    wagon_weighments?: RakeWagonWeighment[];
}

interface LoadState {
    active_step: string;
    attempt_no: number;
    failure_reason: string | null;
}

interface RakeData {
    id: number;
    rake_number: string;
    state: string;
    siding?: Siding | null;
    wagons: Wagon[];
    txr: TxrRecord | null;
    rakeLoad?: RakeLoad | null;
    rake_load?: RakeLoad | null;
    wagonLoadings?: RakeWagonLoading[];
    wagon_loadings?: RakeWagonLoading[];
    guardInspections?: GuardInspectionRecord[];
    guard_inspections?: GuardInspectionRecord[];
    weighments?: WeighmentRecord[];
}

interface Props {
    rake: RakeData;
    loadState: LoadState;
    demurrage_rate_per_mt_hour?: number;
}

const STEPS = [
    { key: 'placement', label: 'Placement', short: '1' },
    { key: 'wagon_loading', label: 'Wagon Loading', short: '2' },
    { key: 'guard_inspection', label: 'Guard Inspection', short: '3' },
    { key: 'dispatch', label: 'Dispatch', short: '4' },
] as const;

function formatRemaining(m: number): string {
    if (m <= 0) return '0m 0s';
    const h = Math.floor(m / 60);
    const min = m % 60;
    if (h > 0) return `${h}h ${min}m 0s`;
    return `${min}m 0s`;
}

function formatRemainingWithSeconds(totalSeconds: number): string {
    if (totalSeconds <= 0) return '0m 0s';
    const h = Math.floor(totalSeconds / 3600);
    const min = Math.floor((totalSeconds % 3600) / 60);
    const sec = totalSeconds % 60;
    if (h > 0) return `${h}h ${min}m ${sec}s`;
    return `${min}m ${sec}s`;
}

function formatDemurrageTime(elapsedMinutes: number, freeTimeMinutes: number): string {
    if (elapsedMinutes <= freeTimeMinutes) return '0h 0m';
    const extraMinutes = elapsedMinutes - freeTimeMinutes;
    const h = Math.floor(extraMinutes / 60);
    const min = extraMinutes % 60;
    return `${h}h ${min}m`;
}

function LiveTimerCard({ load }: { load: RakeLoad }) {
    const [elapsedSeconds, setElapsedSeconds] = useState(() =>
        Math.floor((Date.now() - new Date(load.placement_time).getTime()) / 1000)
    );
    const elapsedMinutes = Math.floor(elapsedSeconds / 60);
    const remainingSeconds = Math.max(0, (load.free_time_minutes * 60) - elapsedSeconds);
    const remainingMinutes = Math.ceil(remainingSeconds / 60);
    const isOverFreeTime = elapsedSeconds > (load.free_time_minutes * 60);
    const isLast30Minutes = !isOverFreeTime && remainingMinutes <= 30;
    const demurrageTime = formatDemurrageTime(elapsedMinutes, load.free_time_minutes);

    useEffect(() => {
        const interval = setInterval(() => {
            setElapsedSeconds(
                Math.floor((Date.now() - new Date(load.placement_time).getTime()) / 1000)
            );
        }, 1000);
        return () => clearInterval(interval);
    }, [load.placement_time]);

    return (
        <Card className={isOverFreeTime ? 'border-orange-500' : isLast30Minutes ? 'border-red-500' : 'border-blue-500'}>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Clock className="size-5" />
                    Loading Timer
                </CardTitle>
                <CardDescription>Free time: {load.free_time_minutes} minutes</CardDescription>
            </CardHeader>
            <CardContent className="text-sm space-y-2">
                <div>Started: {new Date(load.placement_time).toLocaleString()}</div>
                <div className="mt-1">
                    <span className="font-medium">Free Time Remaining: </span>
                    <span className={`font-bold ${
                        isOverFreeTime ? 'text-orange-600' : 
                        isLast30Minutes ? 'text-red-600' : 
                        'text-blue-600'
                    }`}>
                        {formatRemainingWithSeconds(remainingSeconds)}
                    </span>
                    {isLast30Minutes && (
                        <span className="ml-2 text-xs text-red-600 font-medium animate-pulse">
                            ⚠️ Less than 30 minutes!
                        </span>
                    )}
                </div>
                {isLast30Minutes && (
                    <div className="mt-2 p-2 bg-red-50 border border-red-200 rounded text-red-700">
                        <div className="font-medium text-sm">⚠️ Warning: Less than 30 minutes remaining!</div>
                        <div className="text-xs mt-1">Complete loading soon to avoid demurrage charges</div>
                    </div>
                )}
                {isOverFreeTime && (
                    <div className="mt-2 p-2 bg-orange-50 border border-orange-200 rounded text-orange-700">
                        <div className="font-medium">Demurrage Time: {demurrageTime}</div>
                        <div className="text-xs mt-1">Extra charges apply</div>
                    </div>
                )}
                <div className="text-xs text-muted-foreground mt-2">
                    Total elapsed: {formatRemainingWithSeconds(elapsedSeconds)}
                </div>
            </CardContent>
        </Card>
    );
}

function HorizontalStepper({
    loadState,
    load,
    attemptNo,
    isCompleted,
    onStepClick,
}: {
    loadState: LoadState;
    load: RakeLoad | null;
    attemptNo: number;
    isCompleted: boolean;
    onStepClick?: (stepKey: string) => void;
}) {
    const stepIndex = STEPS.findIndex((s) => s.key === loadState.active_step);

    return (
        <div className="flex flex-wrap items-center gap-1 border-b pb-4">
            {STEPS.map((step, idx) => {
                const isActive = loadState.active_step === step.key;
                const isPast = stepIndex > idx;
                const isLocked = !load && step.key !== 'placement';
                const showAttempt = step.key === 'wagon_loading' && attemptNo > 1;
                const isClickable = isCompleted || (!isLocked && (isPast || isActive));

                let borderClass = 'border-transparent';
                if (isActive) borderClass = 'border-b-4 border-blue-500';
                else if (isPast) borderClass = 'border-b-4 border-green-500';
                else if (isLocked) borderClass = 'border-transparent';

                return (
                    <div
                        key={step.key}
                        className={`flex items-center gap-1 px-3 py-2 ${
                            isClickable ? 'cursor-pointer hover:bg-muted/50' : 
                            isLocked ? 'cursor-not-allowed text-gray-400' : ''
                        } ${borderClass}`}
                        onClick={() => isClickable && onStepClick && onStepClick(step.key)}
                    >
                        <span className="font-medium">
                            {step.label}
                            {showAttempt && (
                                <span className="ml-1 text-xs text-muted-foreground">(Attempt #{attemptNo})</span>
                            )}
                        </span>
                        {idx < STEPS.length - 1 && (
                            <span className="text-muted-foreground">→</span>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

function PlacementStep({ rake, onSuccess }: { rake: RakeData; onSuccess: () => void }) {
    const { processing } = useForm({});

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Package className="size-5" />
                    Confirm Placement
                </CardTitle>
                <CardDescription>
                    Confirm rake placement. Timer starts when you click. TXR must be completed first.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {!rake.txr || rake.txr.status !== 'completed' ? (
                    <p className="text-sm text-muted-foreground">Complete TXR first, then confirm placement.</p>
                ) : (
                    <Button
                        onClick={() => {
                            router.post(`/rakes/${rake.id}/load/confirm-placement`, {}, {
                                preserveScroll: true,
                                onSuccess: () => onSuccess(),
                            });
                        }}
                        disabled={processing}
                    >
                        Confirm Placement
                    </Button>
                )}
            </CardContent>
        </Card>
    );
}

function WagonLoadingStep({
    rake,
    loadState,
    load,
}: {
    rake: RakeData;
    loadState: LoadState;
    load: RakeLoad;
}) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const attemptNo = loadState.attempt_no;
    const wagonLoadings = load.wagon_loadings ?? load.wagonLoadings ?? [];
    const currentLoadings = wagonLoadings.filter((l) => l.attempt_no === attemptNo);
    const previousAttempts = [...new Set(wagonLoadings.map((l) => l.attempt_no))].filter((a) => a < attemptNo).sort((a, b) => b - a);
    const wagonsOrdered = [...rake.wagons].sort((a, b) => a.wagon_sequence - b.wagon_sequence);
    const fitWagons = wagonsOrdered.filter((w) => !w.is_unfit);
    const fitWithPositiveQty = fitWagons.filter((w) => {
        const l = currentLoadings.find((cl) => cl.wagon_id === w.id);
        return l !== undefined && Number(l.loaded_quantity_mt) > 0;
    }).length;

    const getLoadingForWagon = (wagonId: number) =>
        currentLoadings.find((l) => l.wagon_id === wagonId);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Train className="size-5" />
                    Wagon Loading
                </CardTitle>
                <CardDescription>
                    Record loader and quantity for any wagon (unfit wagons optional for audit). Workflow
                    advances when every <strong>fit</strong> wagon has quantity &gt; 0. Attempt #{attemptNo}:{' '}
                    {fitWithPositiveQty}/{fitWagons.length} fit wagons with quantity; {currentLoadings.length}{' '}
                    total row(s) this attempt.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {previousAttempts.length > 0 && (
                    <Collapsible>
                        <CollapsibleTrigger asChild>
                            <Button variant="ghost" size="sm" className="gap-1">
                                <ChevronRight className="size-4" />
                                Previous attempts summary
                            </Button>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <div className="mt-2 space-y-2 rounded border p-3 text-sm">
                                {previousAttempts.map((att) => {
                                    const loadings = wagonLoadings.filter((l) => l.attempt_no === att);
                                    return (
                                        <div key={att} className="space-y-1">
                                            <div className="font-medium">Attempt #{att}</div>
                                            <ul className="list-inside list-disc text-muted-foreground">
                                                {loadings.map((l) => (
                                                    <li key={l.id}>
                                                        {l.wagon?.wagon_number}: {l.loaded_quantity_mt} MT
                                                        {l.loader && ` (${l.loader.loader_name})`}
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    );
                                })}
                            </div>
                        </CollapsibleContent>
                    </Collapsible>
                )}

                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Wagon Number</TableHead>
                                <TableHead>Tare Weight</TableHead>
                                <TableHead>PCC Weight</TableHead>
                                <TableHead>Loader</TableHead>
                                <TableHead>Loaded Qty (MT)</TableHead>
                                <TableHead>Attempt</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {wagonsOrdered.map((wagon) => {
                                const loading = getLoadingForWagon(wagon.id);
                                return (
                                    <WagonLoadingRow
                                        key={wagon.id}
                                        wagon={wagon}
                                        loading={loading}
                                        loaders={rake.siding?.loaders ?? []}
                                        attemptNo={attemptNo}
                                        rakeId={rake.id}
                                        errors={errors}
                                    />
                                );
                            })}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    );
}

function WagonLoadingRow({
    wagon,
    loading,
    loaders,
    attemptNo,
    rakeId,
    errors,
}: {
    wagon: Wagon;
    loading: RakeWagonLoading | undefined;
    loaders: { id: number; loader_name: string; code: string }[];
    attemptNo: number;
    rakeId: number;
    errors?: Record<string, string>;
}) {
    const { data, setData, post, processing } = useForm({
        wagon_id: wagon.id,
        loader_id: '',
        loaded_quantity_mt: '',
    });

    const unfitRowClass =
        wagon.is_unfit === true
            ? 'bg-red-950/40 dark:bg-red-950/50 border-b border-red-900/55'
            : undefined;

    if (loading) {
        return (
            <TableRow className={unfitRowClass}>
                <TableCell className="font-medium">
                    {wagon.is_unfit && (
                        <span className="mr-2 text-xs font-semibold uppercase text-red-800 dark:text-red-200">
                            Unfit
                        </span>
                    )}
                    {wagon.wagon_number}
                </TableCell>
                <TableCell>{wagon.tare_weight_mt ?? '–'}</TableCell>
                <TableCell>{wagon.pcc_weight_mt ?? '–'}</TableCell>
                <TableCell>{loading.loader?.loader_name ?? '–'}</TableCell>
                <TableCell>{loading.loaded_quantity_mt}</TableCell>
                <TableCell>{attemptNo}</TableCell>
                <TableCell>
                    <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/50 dark:text-green-400">
                        Loaded
                    </span>
                </TableCell>
                <TableCell></TableCell>
            </TableRow>
        );
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/rakes/${rakeId}/load/wagon`, { preserveScroll: true });
    };

    return (
        <TableRow className={unfitRowClass}>
            <TableCell className="font-medium">
                {wagon.is_unfit && (
                    <span className="mr-2 text-xs font-semibold uppercase text-red-800 dark:text-red-200">
                        Unfit
                    </span>
                )}
                {wagon.wagon_number}
            </TableCell>
            <TableCell>{wagon.tare_weight_mt ?? '–'}</TableCell>
            <TableCell>{wagon.pcc_weight_mt ?? '–'}</TableCell>
            <TableCell>
                <select
                    value={data.loader_id}
                    onChange={(e) => setData('loader_id', e.target.value)}
                    className="w-full min-w-[120px] rounded border border-input bg-background px-2 py-1.5 text-sm"
                    required
                >
                    <option value="">Select</option>
                    {loaders.map((l) => (
                        <option key={l.id} value={l.id}>
                            {l.loader_name} ({l.code})
                        </option>
                    ))}
                </select>
            </TableCell>
            <TableCell>
                <Input
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.loaded_quantity_mt}
                    onChange={(e) => setData('loaded_quantity_mt', e.target.value)}
                    className="h-8 w-24"
                    required
                />
            </TableCell>
            <TableCell>{attemptNo}</TableCell>
            <TableCell>
                <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/50 dark:text-amber-400">
                    Pending
                </span>
            </TableCell>
            <TableCell>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(`/rakes/${rakeId}/load/wagon`, { preserveScroll: true });
                    }}
                    className="flex items-center gap-1"
                >
                    <Button
                        type="submit"
                        size="sm"
                        disabled={processing || !data.loader_id || !data.loaded_quantity_mt}
                    >
                        Submit
                    </Button>
                </form>
            </TableCell>
        </TableRow>
    );
}

function GuardInspectionStep({ rake, load, loadState }: { rake: RakeData; load: RakeLoad; loadState: LoadState }) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const { data, setData, post, processing, reset } = useForm({
        inspection_time: new Date().toISOString().slice(0, 16),
        movement_permission_time: new Date().toISOString().slice(0, 16),
        is_approved: true,
        remarks: '',
    });

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(`/rakes/${rake.id}/load/guard-inspection`, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const inspections = load.guard_inspections ?? load.guardInspections ?? [];

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <ShieldCheck className="size-5" />
                    Guard Inspection
                </CardTitle>
                <CardDescription>
                    Guard inspects rake surroundings. Approve to proceed to dispatch. Remarks required if rejected.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {inspections.length > 0 && (
                    <div className="space-y-2">
                        <Label>Inspection History</Label>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Time</TableHead>
                                    <TableHead>Movement Permission</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Remarks</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {inspections.map((i) => (
                                    <TableRow key={i.id}>
                                        <TableCell>{new Date(i.inspection_time).toLocaleString()}</TableCell>
                                        <TableCell>
                                            {i.movement_permission_time
                                                ? new Date(i.movement_permission_time).toLocaleString()
                                                : '–'}
                                        </TableCell>
                                        <TableCell>
                                            <span
                                                className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                                    i.is_approved
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400'
                                                        : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400'
                                                }`}
                                            >
                                                {i.is_approved ? 'Approved' : 'Rejected'}
                                            </span>
                                        </TableCell>
                                        <TableCell>{i.remarks ?? '–'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <Label htmlFor="inspection_time">Inspection Time</Label>
                            <Input
                                id="inspection_time"
                                type="datetime-local"
                                value={data.inspection_time}
                                onChange={(e) => setData('inspection_time', e.target.value)}
                                required
                            />
                            <InputError message={errors?.inspection_time} />
                        </div>
                        <div>
                            <Label htmlFor="movement_permission_time">Movement Permission Time</Label>
                            <Input
                                id="movement_permission_time"
                                type="datetime-local"
                                value={data.movement_permission_time}
                                onChange={(e) => setData('movement_permission_time', e.target.value)}
                                required
                            />
                            <InputError message={errors?.movement_permission_time} />
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="is_approved"
                            checked={data.is_approved}
                            onChange={(e) => setData('is_approved', e.target.checked)}
                            className="rounded"
                        />
                        <Label htmlFor="is_approved">Approved</Label>
                    </div>
                    {!data.is_approved && (
                        <div>
                            <Label htmlFor="remarks">Remarks (required when rejected)</Label>
                            <textarea
                                id="remarks"
                                value={data.remarks}
                                onChange={(e) => setData('remarks', e.target.value)}
                                rows={3}
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                required={!data.is_approved}
                            />
                            <InputError message={errors?.remarks} />
                        </div>
                    )}
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            {data.is_approved ? 'Approve' : 'Reject'}
                        </Button>
                        <Button type="button" variant="outline" onClick={() => reset()}>
                            Cancel
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function DispatchStep({
    rake,
    load,
    demurrageRate,
    attemptNo,
    wagonLoadings,
}: {
    rake: RakeData;
    load: RakeLoad;
    demurrageRate: number;
    attemptNo: number;
    wagonLoadings: RakeWagonLoading[];
}) {
    const { post, processing } = useForm({});
    const elapsedMinutes = Math.floor(
        (Date.now() - new Date(load.placement_time).getTime()) / 60000
    );
    const extraMinutes = Math.max(0, elapsedMinutes - load.free_time_minutes);
    const demurrageHours = Math.ceil(extraMinutes / 60);
    const totalWeight =
        wagonLoadings
            .filter((l) => l.attempt_no === attemptNo)
            .reduce((sum, l) => sum + parseFloat(l.loaded_quantity_mt), 0);
    const projectedDemurrage =
        demurrageHours > 0 ? demurrageHours * totalWeight * demurrageRate : 0;

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Train className="size-5" />
                    Dispatch
                </CardTitle>
                <CardDescription>
                    Lifecycle summary. Confirm dispatch when ready.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <div className="text-sm font-medium text-muted-foreground">Total time elapsed</div>
                        <div className="text-lg font-semibold">{formatRemaining(elapsedMinutes)}</div>
                    </div>
                    <div>
                        <div className="text-sm font-medium text-muted-foreground">Free time</div>
                        <div className="text-lg font-semibold">{load.free_time_minutes} min</div>
                    </div>
                    {demurrageHours > 0 && (
                        <>
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">Demurrage hours</div>
                                <div className="text-lg font-semibold text-amber-600">{demurrageHours} h</div>
                            </div>
                            <div>
                                <div className="text-sm font-medium text-muted-foreground">Projected demurrage</div>
                                <div className="text-lg font-semibold text-amber-600">
                                    ₹{projectedDemurrage.toLocaleString()}
                                </div>
                            </div>
                        </>
                    )}
                </div>

                <div className="text-sm text-muted-foreground">
                    Formula: elapsed_minutes = now − placement_time. If elapsed &gt; free_time, extra_hours =
                    ceil((elapsed − free_time) / 60). Demurrage = extra_hours × weight_mt × rate.
                </div>

                {load.status !== 'completed' && (
                    <form onSubmit={(e) => {
                        e.preventDefault();
                        post(`/rakes/${rake.id}/load/confirm-dispatch`, {
                            onSuccess: () => router.visit(`/rakes/${rake.id}`),
                        });
                    }}>
                        <Button
                            type="submit"
                            disabled={processing}
                        >
                            {processing ? 'Confirming...' : 'Confirm Dispatch'}
                        </Button>
                    </form>
                )}

                {load.status === 'completed' && (
                    <div className="flex items-center gap-2 text-green-600">
                        <ShieldCheck className="size-5" />
                        <span className="font-medium">Dispatch Confirmed</span>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function RakesLoad({ rake, loadState, demurrage_rate_per_mt_hour = 50 }: Props) {
    const load = rake.rake_load ?? rake.rakeLoad;
    const isCompleted = load?.status === 'completed';
    const [activeStep, setActiveStep] = useState(loadState.active_step);
    const [liveLoadState, setLiveLoadState] = useState(loadState);
    const [liveLoad, setLiveLoad] = useState(load);

    // Set up broadcasting for live updates
    useRakeLoadBroadcasting(rake.id, {
        onLoadUpdated: (data) => {
            console.log('Live load update received:', data);
            setLiveLoadState(data.load_state);
            setLiveLoad(data.rake_load);

            // Update active step if not completed
            if (!isCompleted) {
                setActiveStep(data.load_state.active_step);
            }
        },
        onWagonLoadingUpdated: (data) => {
            console.log('Live wagon loading update:', data);
            router.reload({ only: ['rake', 'loadState'] });
        },
        onGuardInspectionUpdated: (data) => {
            console.log('Live guard inspection update:', data);
            router.reload({ only: ['rake', 'loadState'] });
        },
    });

    // Update active step when loadState changes (only when not completed)
    useEffect(() => {
        if (!isCompleted) {
            setActiveStep(liveLoadState.active_step);
        }
    }, [liveLoadState.active_step, isCompleted]);

    // Update local state when props change
    useEffect(() => {
        setLiveLoadState(loadState);
        setLiveLoad(load);
    }, [loadState, load]);

    const handleStepClick = (stepKey: string) => {
        if (isCompleted) {
            setActiveStep(stepKey);
        }
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Rakes', href: '/rakes' },
        { title: rake.rake_number, href: `/rakes/${rake.id}` },
        { title: 'Loading', href: `/rakes/${rake.id}/load` },
    ];

    const currentLoad = liveLoad || load;
    const currentLoadState = liveLoadState;
    const currentStep = isCompleted ? activeStep : currentLoadState.active_step;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Rake ${rake.rake_number} Loading`} />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title={`Rake ${rake.rake_number} – Loading`}
                        description={
                            rake.siding ? `${rake.siding.name} (${rake.siding.code})` : 'Coal loading process'
                        }
                    />
                    <Link href={`/rakes/${rake.id}`}>
                        <Button variant="outline" size="sm">
                            ← Back to rake
                        </Button>
                    </Link>
                </div>

                {currentLoad && (
                    <HorizontalStepper
                        loadState={{ ...currentLoadState, active_step: currentStep }}
                        load={currentLoad}
                        attemptNo={currentLoadState.attempt_no}
                        isCompleted={isCompleted}
                        onStepClick={handleStepClick}
                    />
                )}

                {!currentLoad && (
                    <PlacementStep rake={rake} onSuccess={() => {}} />
                )}

                {currentLoad && (
                    <>
                        {currentLoad.status !== 'completed' && (
                            <LiveTimerCard load={currentLoad} />
                        )}

                        {currentStep === 'placement' && (
                            <PlacementStep rake={rake} onSuccess={() => {}} />
                        )}
                        {currentStep === 'wagon_loading' && (
                            <WagonLoadingStep rake={rake} loadState={currentLoadState} load={currentLoad} />
                        )}
                        {currentStep === 'guard_inspection' && currentLoad && (
                            <GuardInspectionStep rake={rake} load={currentLoad} loadState={currentLoadState} />
                        )}
                        {currentStep === 'dispatch' && currentLoad && (
                            <DispatchStep
                                rake={rake}
                                load={currentLoad}
                                demurrageRate={demurrage_rate_per_mt_hour ?? 50}
                                attemptNo={currentLoadState.attempt_no}
                                wagonLoadings={currentLoad.wagon_loadings ?? currentLoad.wagonLoadings ?? []}
                            />
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
