import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Scale } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Rake {
    id: number;
    rake_number: string;
    siding?: Siding | null;
}

interface Point {
    point: string;
    value_a: number | null;
    value_b: number | null;
    variance_mt: number | null;
    variance_pct: number | null;
    status: string;
}

interface Props {
    rake: Rake;
    points: Point[];
}

export default function ReconciliationShow({ rake, points }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Reconciliation', href: '/reconciliation' },
        {
            title: `Rake ${rake.rake_number}`,
            href: `/reconciliation/${rake.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Reconciliation – ${rake.rake_number}`} />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-medium">
                        Five-point reconciliation: Rake {rake.rake_number}
                    </h2>
                    <div className="flex gap-2">
                        <Link href="/reconciliation">
                            <Button variant="outline">Back to list</Button>
                        </Link>
                        <Link href={`/rakes/${rake.id}`}>
                            <Button variant="outline">View rake</Button>
                        </Link>
                    </div>
                </div>
                {points.map((p) => (
                    <Card key={p.point}>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Scale className="size-4" />
                                {p.point}
                            </CardTitle>
                            <CardDescription>
                                Status: {p.status}
                                {p.variance_pct != null &&
                                    ` · Variance: ${p.variance_pct}%`}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-2 text-sm">
                            <p>
                                <span className="text-muted-foreground">
                                    Source A:
                                </span>{' '}
                                {p.value_a != null ? `${p.value_a} MT` : '—'}
                            </p>
                            <p>
                                <span className="text-muted-foreground">
                                    Source B:
                                </span>{' '}
                                {p.value_b != null ? `${p.value_b} MT` : '—'}
                            </p>
                            {p.variance_mt != null && (
                                <p>
                                    <span className="text-muted-foreground">
                                        Variance:
                                    </span>{' '}
                                    {p.variance_mt} MT
                                </p>
                            )}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AppLayout>
    );
}
