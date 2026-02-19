import {
    Alert,
    AlertDescription,
    AlertTitle,
} from '@/components/ui/alert';
import { cn } from '@/lib/utils';
import { Info } from 'lucide-react';

export interface RrmcsGuidanceProps {
    title: string;
    before: string;
    after: string;
    className?: string;
}

/**
 * In-app guidance for RRMCS: "Old way (Excel/paper)" vs "New way (this app)".
 * Helps superadmin/manager relate each section to their previous workflow.
 */
export function RrmcsGuidance({ title, before, after, className }: RrmcsGuidanceProps) {
    return (
        <Alert variant="default" className={cn('py-4', className)}>
            <Info className="size-4" />
            <AlertTitle className="font-medium">{title}</AlertTitle>
            <AlertDescription asChild>
                <div className="mt-2 space-y-3 text-sm text-muted-foreground">
                    <p>
                        <span className="font-medium text-foreground/90">Before:</span>{' '}
                        {before}
                    </p>
                    <p>
                        <span className="font-medium text-foreground/90">After:</span>{' '}
                        {after}
                    </p>
                </div>
            </AlertDescription>
        </Alert>
    );
}
