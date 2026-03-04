import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface TachographDownloadRecord {
    id: number;
    download_date: string;
    status?: string;
    driver?: { id: number; first_name: string; last_name: string };
}
interface Props {
    tachographDownload: TachographDownloadRecord;
}

export default function FleetTachographDownloadsShow({
    tachographDownload,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/tachograph-downloads' },
        { title: 'Tachograph downloads', href: '/fleet/tachograph-downloads' },
        {
            title: `#${tachographDownload.id}`,
            href: `/fleet/tachograph-downloads/${tachographDownload.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Fleet – Tachograph download #${tachographDownload.id}`}
            />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{`Tachograph download #${tachographDownload.id}`}</h1>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/tachograph-downloads">Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Download date:</span>{' '}
                            {new Date(
                                tachographDownload.download_date,
                            ).toLocaleDateString()}
                        </p>
                        {tachographDownload.status && (
                            <p>
                                <span className="font-medium">Status:</span>{' '}
                                {tachographDownload.status}
                            </p>
                        )}
                        {tachographDownload.driver && (
                            <p>
                                <span className="font-medium">Driver:</span>{' '}
                                <Link
                                    href={`/fleet/drivers/${tachographDownload.driver.id}`}
                                    className="underline"
                                >
                                    {tachographDownload.driver.first_name}{' '}
                                    {tachographDownload.driver.last_name}
                                </Link>
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
