import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface Props {
    creditBalance: number;
    creditsHref?: string;
}

export default function CreditBalanceCard({
    creditBalance,
    creditsHref,
}: Props) {
    const href = creditsHref ?? '/billing/credits';
    return (
        <Card>
            <CardHeader>
                <CardTitle>Credits</CardTitle>
                <CardDescription>
                    Balance: {creditBalance} credits
                </CardDescription>
                <CardContent className="pt-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={href}>Buy credits</Link>
                    </Button>
                </CardContent>
            </CardHeader>
        </Card>
    );
}
