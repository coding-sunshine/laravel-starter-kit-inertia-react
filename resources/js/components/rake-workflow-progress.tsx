import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { AlertCircle, CheckCircle2, Clock } from 'lucide-react';

export interface WorkflowSteps {
    txr_done: boolean;
    wagon_loading_done: boolean;
    guard_done: boolean;
    weighment_done: boolean;
    rr_done: boolean;
}

export const RAKE_WORKFLOW_STEP_LABELS: Array<{
    key: keyof WorkflowSteps;
    label: string;
}> = [
    { key: 'txr_done', label: 'TXR' },
    { key: 'wagon_loading_done', label: 'Wagon loading' },
    { key: 'guard_done', label: 'Guard inspection' },
    { key: 'weighment_done', label: 'Weighment' },
    { key: 'rr_done', label: 'RR document' },
];

export function RakeWorkflowProgressCell({ steps }: { steps: WorkflowSteps }) {
    const allDone = RAKE_WORKFLOW_STEP_LABELS.every(({ key }) => steps[key]);
    const tooltipContent = (
        <div className="space-y-1 text-left">
            {RAKE_WORKFLOW_STEP_LABELS.map(({ key, label }) => (
                <div key={key} className="flex items-center gap-2">
                    {steps[key] ? (
                        <CheckCircle2 className="size-3.5 shrink-0 text-green-500" />
                    ) : (
                        <Clock className="size-3.5 shrink-0 text-amber-500" />
                    )}
                    <span>
                        {label}: {steps[key] ? 'Done' : 'Pending'}
                    </span>
                </div>
            ))}
        </div>
    );
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <button
                    type="button"
                    className="inline-flex cursor-default items-center justify-center rounded p-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    onClick={(e) => e.preventDefault()}
                >
                    {allDone ? (
                        <CheckCircle2 className="size-5 text-green-600 dark:text-green-400" />
                    ) : (
                        <AlertCircle className="size-5 text-amber-600 dark:text-amber-400" />
                    )}
                </button>
            </TooltipTrigger>
            <TooltipContent side="left" className="max-w-xs">
                {tooltipContent}
            </TooltipContent>
        </Tooltip>
    );
}
