import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { TxrWorkflow } from './TxrWorkflow';
import { WagonLoadingWorkflow } from './WagonLoadingWorkflow';
import { GuardInspectionWorkflow } from './GuardInspectionWorkflow';
import { WeighmentWorkflow } from './WeighmentWorkflow';
import { ComparisonWorkflow } from './ComparisonWorkflow';
import { RrDocumentWorkflow } from './RrDocumentWorkflow';
import { PenaltiesWorkflow } from './PenaltiesWorkflow';
import { useState, useEffect } from 'react';

interface RakeData {
    id: number;
    state: string;
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
            gross_weight_mt: string;
            net_weight_mt: string;
            wagon: {
                id: number;
                wagon_number: string;
                wagon_sequence: number;
            };
        }>;
    }>;
    rrDocuments?: Array<{
        id: number;
        rr_number: string;
        rr_received_date: string;
        rr_weight_mt: string | null;
        document_status: string;
    }>;
    penalties?: Array<{
        id: number;
        penalty_type: string;
        penalty_amount: string;
        penalty_status: string;
        penalty_date: string;
        description: string | null;
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
}

export function RakeWorkflow({ rake, demurrage_rate_per_mt_hour }: RakeWorkflowProps) {
    const [rakeData, setRakeData] = useState(rake);
    useEffect(() => {
        setRakeData(rake);
    }, [rake]);

    // Workflow step completion checks
    const isTxrCompleted = rakeData.txr?.status === 'completed';
    const wagonLoadings = rakeData.wagonLoadings ?? [];
    const fitWagonCount = rakeData.wagons.filter(w => !w.is_unfit).length;
    const isWagonLoadingCompleted = wagonLoadings.length > 0 && wagonLoadings.length === fitWagonCount;
    const isGuardApproved = rakeData.guardInspections?.[0]?.is_approved;
    const isWeighmentCompleted = rakeData.weighments?.[0]?.status === 'success';
    const hasRrDocument = !!rakeData.rrDocuments?.length;

    // Disable logic based on workflow
    // const disableWagonLoading = !isTxrCompleted;
    // const disableGuardInspection = !isWagonLoadingCompleted;
    // const disableWeighment = !isGuardApproved;
    // const disableComparison = !isWeighmentCompleted;
    // const disableRrDocument = !isWeighmentCompleted;
    // const disablePenalties = !isWeighmentCompleted;
    
    // Temporarily disable all step requirements
    const disableWagonLoading = false;
    const disableGuardInspection = false;
    const disableWeighment = false;
    const disableComparison = false;
    const disableRrDocument = false;
    const disablePenalties = false;

    return (
        <div className="space-y-4">
            <Accordion type="multiple" /* defaultValue={['txr']} */ className="w-full">
                {/* 1. TXR */}
                <AccordionItem value="txr">
                    <AccordionTrigger>
                        <div className="flex items-center gap-2 text-left">
                            <span className="font-medium">1. TXR - Train Examination Report</span>
                            {isTxrCompleted && (
                                <span className="text-green-600 text-sm">✓ Completed</span>
                            )}
                        </div>
                    </AccordionTrigger>
                    <AccordionContent>
                        <TxrWorkflow
                            rake={rakeData}
                            disabled={false}
                            onUnfitLogsSaved={(logs) =>
                                setRakeData((prev) => ({
                                    ...prev,
                                    txr: prev.txr
                                        ? { ...prev.txr, wagonUnfitLogs: logs }
                                        : null,
                                }))
                            }
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
                    <AccordionTrigger disabled={disableWagonLoading}>
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
                        <WagonLoadingWorkflow rake={rakeData} disabled={disableWagonLoading} />
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
            </Accordion>
        </div>
    );
}
