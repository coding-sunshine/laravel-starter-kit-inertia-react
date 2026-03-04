import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Permit {
    id: number;
    permit_number: string;
    title: string;
    description?: string;
    valid_from: string;
    valid_to: string;
    status: string;
    conditions?: string;
    issued_by?: { id: number; name: string };
    issued_to?: { id: number; name: string };
    location?: { id: number; name: string };
    vehicle?: { id: number; registration: string };
}
interface Props {
    permitToWork: Permit;
}

export default function PermitToWorkShow({ permitToWork }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Permit to work', href: '/fleet/permit-to-work' },
        {
            title: permitToWork.permit_number,
            href: `/fleet/permit-to-work/${permitToWork.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Permit to work" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {permitToWork.permit_number}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/permit-to-work/${permitToWork.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/permit-to-work">Back</Link>
                        </Button>
                    </div>
                </div>
                <dl className="max-w-md space-y-2 rounded-lg border p-6">
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Permit number
                        </dt>
                        <dd className="font-medium">
                            {permitToWork.permit_number}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Title</dt>
                        <dd>{permitToWork.title}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Issued by
                        </dt>
                        <dd>{permitToWork.issued_by?.name ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Issued to
                        </dt>
                        <dd>{permitToWork.issued_to?.name ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Location
                        </dt>
                        <dd>{permitToWork.location?.name ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Vehicle
                        </dt>
                        <dd>{permitToWork.vehicle?.registration ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Valid from
                        </dt>
                        <dd>{permitToWork.valid_from}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Valid to
                        </dt>
                        <dd>{permitToWork.valid_to}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Status
                        </dt>
                        <dd>{permitToWork.status}</dd>
                    </div>
                    {permitToWork.description && (
                        <div>
                            <dt className="text-sm text-muted-foreground">
                                Description
                            </dt>
                            <dd className="whitespace-pre-wrap">
                                {permitToWork.description}
                            </dd>
                        </div>
                    )}
                    {permitToWork.conditions && (
                        <div>
                            <dt className="text-sm text-muted-foreground">
                                Conditions
                            </dt>
                            <dd className="whitespace-pre-wrap">
                                {permitToWork.conditions}
                            </dd>
                        </div>
                    )}
                </dl>
            </div>
        </AppLayout>
    );
}
