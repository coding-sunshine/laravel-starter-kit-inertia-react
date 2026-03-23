import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
    station_code: string | null;
}

interface Rake {
    id: number;
    rake_number: string;
    rake_type: string | null;
    wagon_count: number | null;
    state: string | null;
    siding?: Siding | null;
}

interface Wagon {
    id: number;
    wagon_sequence: number | null;
    wagon_number: string;
    wagon_type: string | null;
}

interface RakeWagonWeighmentRow {
    id: number;
    wagon_id: number | null;
    wagon_number?: string | null;
    wagon_sequence: number | null;
    wagon_type: string | null;
    axles: number | null;
    cc_capacity_mt: string | null;
    printed_tare_mt: string | null;
    actual_gross_mt: string | null;
    actual_tare_mt: string | null;
    net_weight_mt: string | null;
    under_load_mt: string | null;
    over_load_mt: string | null;
    speed_kmph: string | null;
    wagon?: Wagon | null;
}

interface Weighment {
    id: number;
    rake_id: number;
    attempt_no: number;
    gross_weighment_datetime: string | null;
    tare_weighment_datetime: string | null;
    train_name: string | null;
    direction: string | null;
    commodity: string | null;
    from_station: string | null;
    to_station: string | null;
    priority_number: string | null;
    pdf_file_path: string | null;
    status: string;
    created_by: number | null;
    created_at: string;
    updated_at: string;
    rake?: Rake | null;
    rake_wagon_weighments?: RakeWagonWeighmentRow[];
}

interface Props {
    weighment: Weighment;
    can_delete_weighment?: boolean;
}

export default function WeighmentShow({
    weighment,
    can_delete_weighment = false,
}: Props) {
    const { flash } = usePage<{ flash?: { success?: string } }>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Weighments', href: '/weighments' },
        { title: `Weighment #${weighment.id}`, href: `/weighments/${weighment.id}` },
    ];

    const rake = weighment.rake;
    const siding = rake?.siding ?? null;
    const rows = weighment.rake_wagon_weighments ?? [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Weighment details" />
            <div className="space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                        {flash.success}
                    </div>
                )}

                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-3xl font-bold">Weighment details</h1>
                    {can_delete_weighment && (
                        <Button
                            variant="destructive"
                            size="sm"
                            type="button"
                            onClick={() => {
                                if (
                                    !window.confirm(
                                        'Remove this historical weighment import? The associated rake and wagon data created from this PDF will be deleted. This cannot be undone.',
                                    )
                                ) {
                                    return;
                                }
                                router.delete(`/weighments/${weighment.id}`);
                            }}
                        >
                            <Trash2 className="mr-2 h-4 w-4" />
                            Delete import
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Rake</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div>
                                <span className="font-medium">Rake number: </span>
                                <span>{rake?.rake_number ?? '-'}</span>
                            </div>
                            <div>
                                <span className="font-medium">Rake type: </span>
                                <span>{rake?.rake_type ?? '-'}</span>
                            </div>
                            <div>
                                <span className="font-medium">Wagon count: </span>
                                <span>{(rake?.wagon_count ?? rows.length) || '-'}</span>
                            </div>
                            <div>
                                <span className="font-medium">State: </span>
                                <span>{rake?.state ?? '-'}</span>
                            </div>
                            <div>
                                <span className="font-medium">Siding: </span>
                                <span>
                                    {siding
                                        ? `${siding.name} (${siding.station_code ?? siding.code})`
                                        : '-'}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Weighment</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div>
                                <span className="font-medium">Train name: </span>
                                <span>{weighment.train_name ?? '-'}</span>
                            </div>
                            <div>
                                <span className="font-medium">Direction: </span>
                                <span>{weighment.direction ?? '-'}</span>
                            </div>
                            <div>
                                <span className="font-medium">Commodity: </span>
                                <span>{weighment.commodity ?? '-'}</span>
                            </div>
                            <div>
                                <span className="font-medium">From → To: </span>
                                <span>
                                    {weighment.from_station ?? '-'} → {weighment.to_station ?? '-'}
                                </span>
                            </div>
                            <div>
                                <span className="font-medium">Priority number: </span>
                                <span>{weighment.priority_number ?? '-'}</span>
                            </div>
                            <div>
                                <span className="font-medium">Gross weighment: </span>
                                <span>
                                    {weighment.gross_weighment_datetime
                                        ? new Date(
                                              weighment.gross_weighment_datetime,
                                          ).toLocaleString()
                                        : '-'}
                                </span>
                            </div>
                            <div>
                                <span className="font-medium">Tare weighment: </span>
                                <span>
                                    {weighment.tare_weighment_datetime
                                        ? new Date(
                                              weighment.tare_weighment_datetime,
                                          ).toLocaleString()
                                        : '-'}
                                </span>
                            </div>
                            <div>
                                <span className="font-medium">Status: </span>
                                <span>{weighment.status}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Rake wagon weighments</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {rows.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No wagon-level weighment data found.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full border-collapse text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="p-2 text-left">Seq</th>
                                            <th className="p-2 text-left">Wagon no.</th>
                                            <th className="p-2 text-left">Type</th>
                                            <th className="p-2 text-right">Axles</th>
                                            <th className="p-2 text-right">CC (MT)</th>
                                            <th className="p-2 text-right">Printed tare (MT)</th>
                                            <th className="p-2 text-right">Gross (MT)</th>
                                            <th className="p-2 text-right">Actual tare (MT)</th>
                                            <th className="p-2 text-right">Net (MT)</th>
                                            <th className="p-2 text-right">Under</th>
                                            <th className="p-2 text-right">Over</th>
                                            <th className="p-2 text-right">Speed (km/h)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.map((row) => (
                                            <tr key={row.id} className="border-b">
                                                <td className="p-2">
                                                    {row.wagon_sequence ?? row.wagon?.wagon_sequence ?? '-'}
                                                </td>
                                                <td className="p-2">
                                                    {row.wagon_number ??
                                                        row.wagon?.wagon_number ??
                                                        '-'}
                                                </td>
                                                <td className="p-2">
                                                    {row.wagon_type ?? row.wagon?.wagon_type ?? '-'}
                                                </td>
                                                <td className="p-2 text-right">
                                                    {row.axles ?? '-'}
                                                </td>
                                                <td className="p-2 text-right">
                                                    {row.cc_capacity_mt ?? '-'}
                                                </td>
                                                <td className="p-2 text-right">
                                                    {row.printed_tare_mt ?? '-'}
                                                </td>
                                                <td className="p-2 text-right">
                                                    {row.actual_gross_mt ?? '-'}
                                                </td>
                                                <td className="p-2 text-right">
                                                    {row.actual_tare_mt ?? '-'}
                                                </td>
                                                <td className="p-2 text-right">
                                                    {row.net_weight_mt ?? '-'}
                                                </td>
                                                <td className="p-2 text-right">
                                                    {row.under_load_mt ?? '-'}
                                                </td>
                                                <td className="p-2 text-right">
                                                    {row.over_load_mt ?? '-'}
                                                </td>
                                                <td className="p-2 text-right">
                                                    {row.speed_kmph ?? '-'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

