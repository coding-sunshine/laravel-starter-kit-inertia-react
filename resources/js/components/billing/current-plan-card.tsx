import { CreditCard } from 'lucide-react';

import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface Props {
    activePlan: { id: number; name: string } | null;
    isOnTrial: boolean;
}

export default function CurrentPlanCard({ activePlan, isOnTrial }: Props) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <CreditCard className="size-5" />
                    Current plan
                </CardTitle>
                <CardDescription>
                    {activePlan ? activePlan.name : 'No active subscription'}
                    {isOnTrial && ' (Trial)'}
                </CardDescription>
            </CardHeader>
        </Card>
    );
}
