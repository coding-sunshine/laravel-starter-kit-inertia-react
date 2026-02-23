import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';

interface Step {
    id: number;
    step_number: number;
    status: string;
    started_at: string | null;
    completed_at: string | null;
}

interface Weighment {
    id: number;
    weighment_type: 'GROSS' | 'TARE';
    weighment_status: string;
    weighment_time: string;
    gross_weight_mt?: number;
    tare_weight_mt?: number;
    net_weight_mt?: number;
}

interface Props {
    unload: {
        arrival_time: string;
        unload_start_time: string | null;
        unload_end_time: string | null;
        state: string;
        steps: Step[];
        weighments: Weighment[];
    };
    actions?: {
        grossWeighment?: React.ReactNode;
        startUnload?: React.ReactNode;
        tareWeighment?: React.ReactNode;
        completeUnload?: React.ReactNode;
    };
    errors?: Record<string, string>;
}

export default function UnloadTimeline({ unload, actions, errors }: Props) {
    const formatTime = (date: string | null) =>
        date ? new Date(date).toLocaleString() : '-';

    const getDotColor = (status: string) => {
        if (status === 'FAILED' || status === 'CANCELLED') return 'bg-destructive';
        if (status === 'COMPLETED' || status === 'PASSED') return 'bg-primary';
        if (status === 'IN_PROGRESS') return 'bg-yellow-500';
        return 'bg-muted-foreground';
    };

    const stepLabel = (stepNumber: number) => {
        switch (stepNumber) {
            case 1:
                return 'Truck Arrived at Siding';
            case 2:
                return 'Gross Weighment';
            case 3:
                return 'Unloading Started';
            case 4:
                return 'Tare Weighment';
            case 5:
                return 'Unload Completed & Stock Updated';
            default:
                return 'Unknown Step';
        }
    };

    const sortedSteps = [...unload.steps].sort(
        (a, b) => a.step_number - b.step_number
    );

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">
                    Truck Journey Timeline
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-6 relative">
                    {sortedSteps.map((step, index) => {
                        const relatedWeighments =
                            step.step_number === 2
                                ? unload.weighments.filter(w => w.weighment_type === 'GROSS')
                                : step.step_number === 4
                                ? unload.weighments.filter(w => w.weighment_type === 'TARE')
                                : [];

                        return (
                            <div key={step.id} className="flex items-start gap-4">
                                <div className="flex flex-col items-center shrink-0">
                                    <div
                                        className={`size-4 rounded-full ${getDotColor(
                                            step.status
                                        )} transition-all duration-200 ${
                                            step.status === 'IN_PROGRESS' ? 'ring-4 ring-yellow-200' : ''
                                        }`}
                                    />
                                    {index !== sortedSteps.length - 1 && (
                                        <div className="w-px h-10 bg-border mt-1" />
                                    )}
                                </div>

                                <div className="min-w-0 w-full">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-foreground">
                                                {stepLabel(step.step_number)}
                                            </p>

                                            {(step.started_at || step.completed_at) && (
                                                <p className="text-xs text-muted-foreground mt-0.5">
                                                    {formatTime(step.completed_at || step.started_at)}
                                                </p>
                                            )}

                                            {relatedWeighments.length > 0 && (
                                                <div className="mt-2 space-y-1">
                                                    {relatedWeighments
                                                        .sort(
                                                            (a, b) =>
                                                                new Date(a.weighment_time).getTime() -
                                                                new Date(b.weighment_time).getTime()
                                                        )
                                                        .map(w => (
                                                            <div
                                                                key={w.id}
                                                                className={`text-xs p-2 rounded border ${
                                                                    w.weighment_status === 'FAIL'
                                                                        ? 'bg-red-50 border-red-200 text-red-700'
                                                                        : w.weighment_status === 'PASS'
                                                                        ? 'bg-green-50 border-green-200 text-green-700'
                                                                        : 'bg-gray-50 border-gray-200 text-gray-600'
                                                                }`}
                                                            >
                                                                <div className="flex justify-between items-center">
                                                                    <span>
                                                                        {w.weighment_type} - {w.weighment_status}
                                                                    </span>
                                                                    <span className="text-xs opacity-75">
                                                                        {formatTime(w.weighment_time)}
                                                                    </span>
                                                                </div>
                                                                {w.gross_weight_mt && (
                                                                    <div className="mt-1">
                                                                        Gross: {w.gross_weight_mt} MT
                                                                        {w.tare_weight_mt && ` / Tare: ${w.tare_weight_mt} MT`}
                                                                        {w.net_weight_mt && ` / Net: ${w.net_weight_mt} MT`}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        ))}
                                                </div>
                                            )}
                                        </div>

                                        {/* Action buttons for corresponding steps */}
                                        {(step.status === 'IN_PROGRESS' || step.status === 'FAILED') && (
                                            <div className="shrink-0">
                                                {step.step_number === 2 && actions?.grossWeighment}
                                                {step.step_number === 3 && actions?.startUnload}
                                                {step.step_number === 4 && actions?.tareWeighment}
                                                {step.step_number === 5 && actions?.completeUnload}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}