import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { TxrWorkflow } from './TxrWorkflow';
import { WagonLoadingWorkflow } from './WagonLoadingWorkflow';
import { GuardInspectionWorkflow } from './GuardInspectionWorkflow';
import { WeighmentWorkflow } from './WeighmentWorkflow';
import { ComparisonWorkflow } from './ComparisonWorkflow';
import { RrDocumentWorkflow } from './RrDocumentWorkflow';
import { PenaltiesWorkflow } from './PenaltiesWorkflow';
import { useState, useEffect } from 'react';
import { Check } from 'lucide-react';
import { useForm, usePage } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';

interface RakeData {
    id: number;
    state: string;
    loading_start_time?: string | null;
    loading_end_time?: string | null;
    loading_free_minutes?: number | null;
    wagons: Array<{
        id: number;
        wagon_number: string;
        wagon_sequence: number;
        is_unfit: boolean;
    }>;
    txr?: {
        id: number;
        inspection_time: string;
        inspection_end_time?: string | null;
        status: string;
        remarks: string | null;
        handwritten_note_url?: string | null;
        wagon_unfit_logs?: Array<{
            id?: number;
            wagon_id: number;
            wagon?: { wagon_number: string; wagon_sequence: number; wagon_type?: string | null };
            reason?: string | null;
            marking_method?: string | null;
            marked_at?: string | null;
        }>;
        wagonUnfitLogs?: Array<{
            id?: number;
            wagon_id: number;
            wagon?: { wagon_number: string; wagon_sequence: number; wagon_type?: string | null };
            reason?: string | null;
            reason_unfit?: string | null;
            marking_method?: string | null;
            marked_at?: string | null;
        }>;
    } | null;
    wagonLoadings?: Array<{
        id: number;
        wagon_id: number;
        loaded_quantity_mt: string;
        loading_time?: string | null;
        remarks?: string | null;
        wagon: {
            id: number;
            wagon_number: string;
            wagon_sequence: number;
            wagon_type?: string | null;
            pcc_weight_mt?: string | null;
        };
        loader?: {
            id: number;
            loader_name: string;
            code: string;
        };
    }>;
    guardInspections?: Array<{
        id: number;
        inspection_time: string;
        movement_permission_time: string;
        is_approved: boolean;
        remarks: string | null;
    }>;
    weighments?: Array<{
        id: number;
        weighment_time: string;
        total_weight_mt: string;
        status: string | null;
        train_speed_kmph: number;
        attempt_no: number;
        wagonWeights?: Array<{
            wagon_id: number;
            gross_weight_mt: number;
            net_weight_mt: number;
            wagon: {
                id: number;
                wagon_number: string;
                wagon_sequence: number;
                pcc_weight_mt?: string | number | null;
            };
        }>;
    }>;
    is_diverted?: boolean;
    diverrtDestinations?: Array<{
        id: number;
        location: string;
    }>;
    rrDocuments?: Array<{
        id: number;
        rr_number: string;
        rr_received_date: string;
        rr_weight_mt: string | null;
        document_status: string;
        diverrt_destination_id?: number | null;
    }>;
    penalties?: Array<{
        id: number;
        penalty_type: string;
        penalty_amount: string;
        penalty_status: string;
        penalty_date: string;
        description: string | null;
    }>;
    appliedPenalties?: Array<{
        id: number;
        amount: string | number;
        quantity?: string | number | null;
        wagon_id?: number | null;
        penalty_type?: { id: number; code: string; name: string; calculation_type: string };
        wagon?: { id: number; wagon_number: string; overload_weight_mt?: string | number | null };
    }>;
    siding?: {
        loaders?: Array<{
            id: number;
            loader_name: string;
            code: string;
        }>;
    } | null;
}

interface RakeWorkflowProps {
    rake: RakeData;
    demurrage_rate_per_mt_hour: number;
    /** Keeps parent wagon list (e.g. View Wagons dialog) in sync after unfit logs save */
    onUnfitWagonIdsSynced?: (unfitWagonIds: number[]) => void;
}

interface PreRrEstimate {
    available: boolean;
    classCode: string;
    distanceKm: number | null;
    actualLoadedWeightMt: number | null;
    sumPccWeightMt: number | null;
    chargeableWeightMt: number | null;
    ratePerMt: number | null;
    freightAmount: number | null;
    otherCharges: number | null;
    penaltyAmount: number | null;
    rebateAmount: number | null;
    gstPercent: number;
    gstAmount: number | null;
    totalAmount: number | null;
    formula: string;
    warnings: string[];
}

function mergeTxrAfterHeaderSave(
    prev: RakeData['txr'],
    incoming: Record<string, unknown>,
): NonNullable<RakeData['txr']> {
    const prevLogs = prev?.wagonUnfitLogs;
    const incomingLogs = incoming.wagonUnfitLogs ?? incoming.wagon_unfit_logs;
    const wagonUnfitLogs = Array.isArray(incomingLogs) ? incomingLogs : (prevLogs ?? []);

    return {
        ...(prev ?? {}),
        id: Number(incoming.id),
        inspection_time: String(incoming.inspection_time ?? ''),
        inspection_end_time: (incoming.inspection_end_time as string | null | undefined) ?? null,
        status: String(incoming.status ?? 'in_progress'),
        remarks: (incoming.remarks as string | null) ?? null,
        handwritten_note_url:
            (incoming.handwritten_note_url as string | null | undefined) ??
            prev?.handwritten_note_url ??
            null,
        wagonUnfitLogs,
    } as NonNullable<RakeData['txr']>;
}

interface LoadingTimesFormProps {
    rakeId: number;
    loadingStart?: string | null;
    loadingEnd?: string | null;
}

function LoadingTimesForm({ rakeId, loadingStart, loadingEnd }: LoadingTimesFormProps) {
    const { flash } = usePage().props as { flash?: { success?: string; error?: string } };

    const toLocalInput = (value?: string | null): string => {
        if (!value) {
            return '';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return '';
        }

        const pad = (n: number) => n.toString().padStart(2, '0');

        const year = date.getFullYear();
        const month = pad(date.getMonth() + 1);
        const day = pad(date.getDate());
        const hours = pad(date.getHours());
        const minutes = pad(date.getMinutes());

        return `${year}-${month}-${day}T${hours}:${minutes}`;
    };

    const {
        data,
        setData,
        processing,
        errors,
        put,
    } = useForm({
        loading_start_time: toLocalInput(loadingStart),
        loading_end_time: toLocalInput(loadingEnd),
    });

    const hasSavedTimes = !!(data.loading_start_time || data.loading_end_time);
    const startDisplay = data.loading_start_time
        ? data.loading_start_time.replace('T', ' ')
        : null;
    const endDisplay = data.loading_end_time
        ? data.loading_end_time.replace('T', ' ')
        : null;

    return (
        <div className="rounded-md border bg-card p-3 space-y-3">
            <p className="text-xs font-medium text-muted-foreground">
                Loading time
            </p>
            {hasSavedTimes && (
                <p className="text-[0.7rem] text-muted-foreground">
                    Last saved:{' '}
                    {startDisplay}
                    {endDisplay ? ` → ${endDisplay}` : ''}
                </p>
            )}
            <div className="grid gap-3 md:grid-cols-2">
                <div className="space-y-1">
                    <Label htmlFor="loading_start_time">
                        Loading start time
                    </Label>
                    <Input
                        id="loading_start_time"
                        type="datetime-local"
                        value={data.loading_start_time}
                        onChange={(e) =>
                            setData('loading_start_time', e.target.value)
                        }
                    />
                    <InputError message={errors.loading_start_time} />
                </div>
                <div className="space-y-1">
                    <Label htmlFor="loading_end_time">
                        Loading end time
                    </Label>
                    <Input
                        id="loading_end_time"
                        type="datetime-local"
                        value={data.loading_end_time}
                        onChange={(e) =>
                            setData('loading_end_time', e.target.value)
                        }
                    />
                    <InputError message={errors.loading_end_time} />
                </div>
            </div>
            <div className="pt-1">
                <Button
                    type="button"
                    size="sm"
                    disabled={processing}
                    onClick={() => put(`/rakes/${rakeId}/loading-times`)}
                >
                    Save loading times
                </Button>
                {flash?.success === 'Loading times updated.' && (
                    <span className="ml-3 text-xs text-emerald-600">
                        Saved.
                    </span>
                )}
            </div>
        </div>
    );
}

export function RakeWorkflow({
    rake,
    demurrage_rate_per_mt_hour,
    onUnfitWagonIdsSynced,
}: RakeWorkflowProps) {
    const { auth } = usePage().props as { auth?: { can_bypass?: boolean } };
    const isSuperAdmin = auth?.can_bypass === true;
    const [rakeData, setRakeData] = useState(rake);
    const [workflowGateError, setWorkflowGateError] = useState<string | null>(null);
    const [preRrEstimate, setPreRrEstimate] = useState<PreRrEstimate | null>(null);
    const [preRrLoading, setPreRrLoading] = useState(false);

    useEffect(() => {
        setRakeData(rake);
    }, [rake]);

    useEffect(() => {
        if (!isSuperAdmin) {
            setPreRrEstimate(null);
            return;
        }

        setPreRrLoading(true);
        fetch(`/rakes/${rake.id}/pre-rr`, {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error('Failed to load PRE-RR estimate');
                }

                return (await response.json()) as PreRrEstimate;
            })
            .then((payload) => setPreRrEstimate(payload))
            .catch(() => setPreRrEstimate(null))
            .finally(() => setPreRrLoading(false));
    }, [isSuperAdmin, rake.id]);

    // Workflow step completion checks — TXR checklist reflects saved start/end times (not only status=completed)
    const isTxrTimesRecorded =
        Boolean(rakeData.txr?.inspection_time) && Boolean(rakeData.txr?.inspection_end_time);
    const wagonLoadings = rakeData.wagonLoadings ?? [];
    const positivelyLoadedWagonIds = new Set(
        wagonLoadings
            .filter(l => Number(l.loaded_quantity_mt) > 0)
            .map(l => l.wagon_id),
    );
    const fitWagons = rakeData.wagons.filter(w => !w.is_unfit);
    const isWagonLoadingCompleted =
        fitWagons.length > 0 &&
        fitWagons.every(w => positivelyLoadedWagonIds.has(w.id));
    const missingWagonNumberCount = rakeData.wagons.filter(
        (wagon) => (wagon.wagon_number ?? '').trim().length <= 4
    ).length;
    const hasMissingWagonNumbers = missingWagonNumberCount > 0;
    const isGuardApproved = rakeData.guardInspections?.[0]?.is_approved;
    const isWeighmentCompleted = rakeData.weighments?.[0]?.status === 'success';
    const hasRrDocument = ((): boolean => {
        const docs = rakeData.rrDocuments ?? [];
        if (!rakeData.is_diverted) {
            return docs.length > 0;
        }
        const primary = docs.some((d) => d.diverrt_destination_id == null);
        if (!primary) {
            return false;
        }
        const legs = rakeData.diverrtDestinations ?? [];
        if (legs.length === 0) {
            return true;
        }
        return legs.every((leg) => docs.some((d) => d.diverrt_destination_id === leg.id));
    })();

    const progressSteps: Array<{
        id: string;
        label: string;
        description: string;
        status: 'completed' | 'pending';
    }> = [
        {
            id: 'indent',
            label: 'Indent creation',
            description: 'Indent created from the incoming PDF.',
            status: 'completed',
        },
        {
            id: 'rake',
            label: 'Rake creation',
            description: 'Rake created and linked to the indent.',
            status: 'completed',
        },
        {
            id: 'txr',
            label: 'TXR',
            description: 'Train Examination Report: start and end inspection times saved.',
            status: isTxrTimesRecorded ? 'completed' : 'pending',
        },
        {
            id: 'loading',
            label: 'Wagon loading',
            description: 'All fit wagons have quantity recorded (unfit rows are optional).',
            status: isWagonLoadingCompleted ? 'completed' : 'pending',
        },
        {
            id: 'guard',
            label: 'Guard inspection',
            description: 'Guard inspection completed and approved.',
            status: isGuardApproved ? 'completed' : 'pending',
        },
        {
            id: 'weighment',
            label: 'Rake weighment',
            description: 'At least one successful rake weighment exists.',
            status: isWeighmentCompleted ? 'completed' : 'pending',
        },
        {
            id: 'rr',
            label: 'Railway receipt (RR)',
            description: 'RR document created and linked to this rake.',
            status: hasRrDocument ? 'completed' : 'pending',
        },
    ];

    const disableTxr = hasMissingWagonNumbers;
    const disableWagonLoading = hasMissingWagonNumbers;
    const disableGuardInspection = false;
    const disableWeighment = false;
    const disableComparison = false;
    const disableRrDocument = false;
    const disablePenalties = false;

    return (
        <div className="space-y-4">
            {/* Inline high-level progress checklist */}
            <div className="rounded-lg border bg-card p-4 space-y-3">
                <p className="text-xs font-medium text-muted-foreground">
                    Overall progress
                </p>
                <ol className="space-y-3">
                    {progressSteps.map((step, index) => {
                        const isCompleted = step.status === 'completed';

                        return (
                            <li key={step.id} className="flex items-start gap-3">
                                <div
                                    className={
                                        'mt-0.5 flex h-6 w-6 items-center justify-center rounded-full border text-[0.65rem] ' +
                                        (isCompleted
                                            ? 'border-emerald-500 bg-emerald-500 text-white'
                                            : 'border-muted-foreground/40 text-muted-foreground')
                                    }
                                >
                                    {isCompleted ? (
                                        <Check className="h-3 w-3" />
                                    ) : (
                                        index + 1
                                    )}
                                </div>
                                <div>
                                    <div
                                        className={
                                            'text-sm font-medium ' +
                                            (isCompleted
                                                ? 'text-emerald-700 dark:text-emerald-400'
                                                : 'text-foreground')
                                        }
                                    >
                                        {`Step ${index + 1}: ${step.label}`}
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {step.description}
                                    </p>
                                </div>
                            </li>
                        );
                    })}
                </ol>
            </div>

            {workflowGateError && (
                <div className="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                    {workflowGateError}
                </div>
            )}

            <Accordion type="multiple" /* defaultValue={['txr']} */ className="w-full">
                {/* 1. TXR */}
                <AccordionItem value="txr">
                    <AccordionTrigger
                        onClick={(event) => {
                            if (disableTxr) {
                                event.preventDefault();
                                setWorkflowGateError('Please add all wagon numbers for this rake.');
                            } else {
                                setWorkflowGateError(null);
                            }
                        }}
                    >
                        <div className="flex items-center gap-2 text-left">
                            <span className="font-medium">1. TXR - Train Examination Report</span>
                            {isTxrTimesRecorded && (
                                <span className="text-green-600 text-sm">✓ Completed</span>
                            )}
                            {disableTxr && !isTxrTimesRecorded && (
                                <span className="text-gray-400 text-sm">🔒 Locked</span>
                            )}
                        </div>
                    </AccordionTrigger>
                    <AccordionContent>
                        <TxrWorkflow
                            rake={rakeData}
                            disabled={disableTxr}
                            onTxrHeaderSaved={(incoming) => {
                                setRakeData((prev) => ({
                                    ...prev,
                                    txr: mergeTxrAfterHeaderSave(
                                        prev.txr,
                                        incoming as Record<string, unknown>,
                                    ),
                                }));
                            }}
                            onUnfitLogsSaved={(logs) => {
                                const unfitIds = [
                                    ...new Set(
                                        logs
                                            .map((l) => Number(l.wagon_id))
                                            .filter((id) => Number.isFinite(id) && id > 0),
                                    ),
                                ];
                                onUnfitWagonIdsSynced?.(unfitIds);
                                setRakeData((prev) => {
                                    const unfitSet = new Set(unfitIds);
                                    return {
                                        ...prev,
                                        txr: prev.txr
                                            ? { ...prev.txr, wagonUnfitLogs: logs }
                                            : null,
                                        wagons: prev.wagons.map((w) => ({
                                            ...w,
                                            is_unfit: unfitSet.has(w.id),
                                        })),
                                    };
                                });
                            }}
                            onTxrNoteUploaded={(url) =>
                                setRakeData((prev) => ({
                                    ...prev,
                                    txr: prev.txr ? { ...prev.txr, handwritten_note_url: url } : null,
                                }))
                            }
                        />
                    </AccordionContent>
                </AccordionItem>

                {/* 2. Wagon Loading */}
                <AccordionItem value="wagon-loading">
                    <AccordionTrigger
                        onClick={(event) => {
                            if (disableWagonLoading) {
                                event.preventDefault();
                                setWorkflowGateError('Please add all wagon numbers for this rake.');
                            } else {
                                setWorkflowGateError(null);
                            }
                        }}
                    >
                        <div className="flex items-center gap-2 text-left">
                            <span className="font-medium">2. Wagon Loading</span>
                            {isWagonLoadingCompleted && (
                                <span className="text-green-600 text-sm">✓ Completed</span>
                            )}
                            {disableWagonLoading && !isWagonLoadingCompleted && (
                                <span className="text-gray-400 text-sm">🔒 Locked</span>
                            )}
                        </div>
                    </AccordionTrigger>
                    <AccordionContent>
                        <LoadingTimesForm rakeId={rakeData.id} loadingStart={rakeData.loading_start_time} loadingEnd={rakeData.loading_end_time} />
                        <div className="mt-4">
                            <WagonLoadingWorkflow
                                rake={rakeData}
                                disabled={disableWagonLoading}
                                onWagonLoadingsSaved={(loadings) =>
                                    setRakeData((prev) => ({
                                        ...prev,
                                        wagonLoadings: loadings,
                                    }))
                                }
                                onWagonUpdated={(wagonId, updates) =>
                                    setRakeData((prev) => ({
                                        ...prev,
                                        wagons: prev.wagons.map((w) =>
                                            w.id === wagonId ? { ...w, wagon_number: updates.wagon_number } : w
                                        ),
                                    }))
                                }
                            />
                        </div>
                    </AccordionContent>
                </AccordionItem>

                {/* 3. Guard Inspection */}
                <AccordionItem value="guard-inspection">
                    <AccordionTrigger disabled={disableGuardInspection}>
                        <div className="flex items-center gap-2 text-left">
                            <span className="font-medium">3. Guard Inspection</span>
                            {isGuardApproved !== undefined && (
                                <span className={isGuardApproved ? "text-green-600 text-sm" : "text-red-600 text-sm"}>
                                    {isGuardApproved ? "✓ Approved" : "✗ Rejected"}
                                </span>
                            )}
                            {disableGuardInspection && isGuardApproved === undefined && (
                                <span className="text-gray-400 text-sm">🔒 Locked</span>
                            )}
                        </div>
                    </AccordionTrigger>
                    <AccordionContent>
                        <GuardInspectionWorkflow rake={rakeData} disabled={disableGuardInspection} />
                    </AccordionContent>
                </AccordionItem>

                {/* 4. Rake Weighment */}
                <AccordionItem value="weighment">
                    <AccordionTrigger disabled={disableWeighment}>
                        <div className="flex items-center gap-2 text-left">
                            <span className="font-medium">4. Rake Weighment</span>
                            {isWeighmentCompleted && (
                                <span className="text-green-600 text-sm">✓ Completed</span>
                            )}
                            {disableWeighment && !isWeighmentCompleted && (
                                <span className="text-gray-400 text-sm">🔒 Locked</span>
                            )}
                        </div>
                    </AccordionTrigger>
                    <AccordionContent>
                        <WeighmentWorkflow rake={rakeData} disabled={disableWeighment} />
                    </AccordionContent>
                </AccordionItem>

                {/* 5. Loader vs Weighment Comparison */}
                <AccordionItem value="comparison">
                    <AccordionTrigger disabled={disableComparison}>
                        <div className="flex items-center gap-2 text-left">
                            <span className="font-medium">5. Loader vs Weighment Comparison</span>
                            {disableComparison && (
                                <span className="text-gray-400 text-sm">🔒 Locked</span>
                            )}
                        </div>
                    </AccordionTrigger>
                    <AccordionContent>
                        <ComparisonWorkflow rake={rakeData} disabled={disableComparison} />
                    </AccordionContent>
                </AccordionItem>

                {/* 6. Railway Receipt Document */}
                <AccordionItem value="rr-document">
                    <AccordionTrigger disabled={disableRrDocument}>
                        <div className="flex items-center gap-2 text-left">
                            <span className="font-medium">6. Railway Receipt (RR) Document</span>
                            {hasRrDocument && (
                                <span className="text-green-600 text-sm">✓ Created</span>
                            )}
                            {disableRrDocument && !hasRrDocument && (
                                <span className="text-gray-400 text-sm">🔒 Locked</span>
                            )}
                        </div>
                    </AccordionTrigger>
                    <AccordionContent>
                        <RrDocumentWorkflow rake={rakeData} disabled={disableRrDocument} />
                    </AccordionContent>
                </AccordionItem>

                {/* 7. Penalties */}
                <AccordionItem value="penalties">
                    <AccordionTrigger disabled={disablePenalties}>
                        <div className="flex items-center gap-2 text-left">
                            <span className="font-medium">7. Penalties</span>
                            {disablePenalties && (
                                <span className="text-gray-400 text-sm">🔒 Locked</span>
                            )}
                        </div>
                    </AccordionTrigger>
                    <AccordionContent>
                        <PenaltiesWorkflow
                            rake={rakeData}
                            demurrage_rate_per_mt_hour={demurrage_rate_per_mt_hour}
                            disabled={disablePenalties}
                        />
                    </AccordionContent>
                </AccordionItem>

                {isSuperAdmin && (
                    <AccordionItem value="pre-rr">
                        <AccordionTrigger>
                            <div className="flex items-center gap-2 text-left">
                                <span className="font-medium">8. PRE-RR (Estimated Freight)</span>
                            </div>
                        </AccordionTrigger>
                        <AccordionContent>
                            {preRrLoading ? (
                                <div className="text-sm text-muted-foreground">Loading PRE-RR estimate...</div>
                            ) : preRrEstimate === null ? (
                                <div className="text-sm text-muted-foreground">
                                    PRE-RR estimate is not available for this rake yet.
                                </div>
                            ) : (
                                <div className="space-y-2 text-sm">
                                    <div>Class Code: <span className="font-medium">{preRrEstimate.classCode}</span></div>
                                    <div>Distance: <span className="font-medium">{preRrEstimate.distanceKm ?? '-'}</span> km</div>
                                    <div>Actual Loaded Weight: <span className="font-medium">{preRrEstimate.actualLoadedWeightMt ?? '-'}</span> MT</div>
                                    <div>Sum PCC Weight: <span className="font-medium">{preRrEstimate.sumPccWeightMt ?? '-'}</span> MT</div>
                                    <div>Chargeable Weight: <span className="font-medium">{preRrEstimate.chargeableWeightMt ?? '-'}</span> MT</div>
                                    <div>Rate: <span className="font-medium">{preRrEstimate.ratePerMt ?? '-'}</span> Rs/MT</div>
                                    <div>Freight: <span className="font-medium">{preRrEstimate.freightAmount ?? '-'}</span></div>
                                    <div>Other Charges: <span className="font-medium">{preRrEstimate.otherCharges ?? 0}</span></div>
                                    <div>GST ({preRrEstimate.gstPercent}%): <span className="font-medium">{preRrEstimate.gstAmount ?? '-'}</span></div>
                                    <div>Penalty: <span className="font-medium">{preRrEstimate.penaltyAmount ?? 0}</span></div>
                                    <div>Rebate: <span className="font-medium">{preRrEstimate.rebateAmount ?? 0}</span></div>
                                    <div className="pt-1 text-base font-semibold">
                                        Estimated Total: {preRrEstimate.totalAmount ?? '-'}
                                    </div>
                                    <div className="text-xs text-muted-foreground">{preRrEstimate.formula}</div>
                                    {preRrEstimate.warnings.length > 0 && (
                                        <div className="rounded-md border border-amber-300 bg-amber-50 p-2 text-amber-800 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                            {preRrEstimate.warnings.join(' ')}
                                        </div>
                                    )}
                                </div>
                            )}
                        </AccordionContent>
                    </AccordionItem>
                )}
            </Accordion>
        </div>
    );
}
