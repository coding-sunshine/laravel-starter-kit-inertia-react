import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';

interface Rake {
    id: number;
    rake_number: string;
    siding?: { id: number; name: string; code: string };
}

interface RrWagon {
    wagon_number?: string;
    wagon_type?: string;
    cc_mt?: number;
    tare_mt?: number;
    gross_mt?: number;
    actual_mt?: number;
    permissible_mt?: number;
    over_weight_mt?: number;
    chargeable_mt?: number;
}

interface RrDocument {
    id: number;
    rake_id: number;
    rr_number: string;
    rr_received_date: string;
    rr_weight_mt: string | null;
    fnr: string | null;
    from_station_code: string | null;
    to_station_code: string | null;
    freight_total: string | null;
    document_status: string;
    has_discrepancy: boolean;
    discrepancy_details: string | null;
    rr_details?: {
        charges?: Record<string, number>;
        wagons?: RrWagon[];
    } | null;
    rake?: Rake;
}

interface Props {
    rrDocument: RrDocument;
}

export default function RailwayReceiptsShow({ rrDocument }: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Railway Receipts', href: '/railway-receipts' },
        {
            title: rrDocument.rr_number,
            href: `/railway-receipts/${rrDocument.id}`,
        },
    ];

    const form = useForm({
        rr_number: rrDocument.rr_number,
        rr_received_date: rrDocument.rr_received_date.split('T')[0],
        rr_weight_mt: rrDocument.rr_weight_mt ?? '',
        fnr: rrDocument.fnr ?? '',
        from_station_code: rrDocument.from_station_code ?? '',
        to_station_code: rrDocument.to_station_code ?? '',
        freight_total: rrDocument.freight_total ?? '',
        document_status: rrDocument.document_status,
        has_discrepancy: rrDocument.has_discrepancy,
        discrepancy_details: rrDocument.discrepancy_details ?? '',
    });

    const charges = rrDocument.rr_details?.charges ?? {};
    const wagons = rrDocument.rr_details?.wagons ?? [];

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/railway-receipts/${rrDocument.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`RR ${rrDocument.rr_number}`} />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-medium">
                        RR {rrDocument.rr_number}
                    </h2>
                    <Button
                        variant="outline"
                        onClick={() => router.get('/railway-receipts')}
                    >
                        Back to list
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                        <CardDescription>
                            Rake: {rrDocument.rake?.rake_number} —{' '}
                            {rrDocument.rake?.siding?.name}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <dl className="grid gap-2 text-sm">
                            <dt className="font-medium">RR number</dt>
                            <dd>{rrDocument.rr_number}</dd>
                            {rrDocument.fnr && (
                                <>
                                    <dt className="font-medium">FNR</dt>
                                    <dd>{rrDocument.fnr}</dd>
                                </>
                            )}
                            <dt className="font-medium">Received date</dt>
                            <dd>{rrDocument.rr_received_date}</dd>
                            <dt className="font-medium">Weight (MT)</dt>
                            <dd>{rrDocument.rr_weight_mt ?? '-'}</dd>
                            {rrDocument.from_station_code && (
                                <>
                                    <dt className="font-medium">
                                        From station
                                    </dt>
                                    <dd>{rrDocument.from_station_code}</dd>
                                </>
                            )}
                            {rrDocument.to_station_code && (
                                <>
                                    <dt className="font-medium">To station</dt>
                                    <dd>{rrDocument.to_station_code}</dd>
                                </>
                            )}
                            {rrDocument.freight_total != null &&
                                rrDocument.freight_total !== '' && (
                                    <>
                                        <dt className="font-medium">
                                            Freight total (₹)
                                        </dt>
                                        <dd>
                                            {Number(
                                                rrDocument.freight_total,
                                            ).toLocaleString('en-IN')}
                                        </dd>
                                    </>
                                )}
                            {Object.keys(charges).length > 0 && (
                                <>
                                    <dt className="font-medium">Charges</dt>
                                    <dd className="space-y-1">
                                        {Object.entries(charges).map(
                                            ([code, amount]) => (
                                                <span
                                                    key={code}
                                                    className="block"
                                                >
                                                    {code}: ₹
                                                    {Number(
                                                        amount,
                                                    ).toLocaleString('en-IN')}
                                                </span>
                                            ),
                                        )}
                                    </dd>
                                </>
                            )}
                            <dt className="font-medium">Status</dt>
                            <dd>{rrDocument.document_status}</dd>
                            {rrDocument.has_discrepancy &&
                                rrDocument.discrepancy_details && (
                                    <>
                                        <dt className="font-medium">
                                            Discrepancy details
                                        </dt>
                                        <dd>
                                            {rrDocument.discrepancy_details}
                                        </dd>
                                    </>
                                )}
                        </dl>
                        {wagons.length > 0 && (
                            <div className="mt-4">
                                <h4 className="mb-2 text-sm font-medium">
                                    Wagon details (from RR)
                                </h4>
                                <div className="overflow-x-auto rounded-md border border-input">
                                    <table className="w-full min-w-[600px] text-left text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="p-2">
                                                    Wagon No
                                                </th>
                                                <th className="p-2">Type</th>
                                                <th className="p-2">CC (MT)</th>
                                                <th className="p-2">
                                                    Actual (MT)
                                                </th>
                                                <th className="p-2">
                                                    Permissible (MT)
                                                </th>
                                                <th className="p-2">
                                                    Over (MT)
                                                </th>
                                                <th className="p-2">
                                                    Chargeable (MT)
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {wagons.map((w, idx) => (
                                                <tr
                                                    // eslint-disable-next-line @eslint-react/no-array-index-key -- wagon list may not have unique id
                                                    key={`${w.wagon_number ?? ''}-${w.wagon_type ?? ''}-${idx}`}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="p-2">
                                                        {w.wagon_number ?? '-'}
                                                    </td>
                                                    <td className="p-2">
                                                        {w.wagon_type ?? '-'}
                                                    </td>
                                                    <td className="p-2">
                                                        {w.cc_mt ?? '-'}
                                                    </td>
                                                    <td className="p-2">
                                                        {w.actual_mt ?? '-'}
                                                    </td>
                                                    <td className="p-2">
                                                        {w.permissible_mt ??
                                                            '-'}
                                                    </td>
                                                    <td className="p-2">
                                                        {w.over_weight_mt ??
                                                            '-'}
                                                    </td>
                                                    <td className="p-2">
                                                        {w.chargeable_mt ?? '-'}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Edit</CardTitle>
                        <CardDescription>Update RR fields</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={handleUpdate}
                            className="max-w-md space-y-4"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="rr_number">RR number *</Label>
                                <Input
                                    id="rr_number"
                                    value={form.data.rr_number}
                                    onChange={(e) =>
                                        form.setData(
                                            'rr_number',
                                            e.target.value,
                                        )
                                    }
                                    className="text-sm"
                                />
                                <InputError
                                    message={
                                        form.errors.rr_number ??
                                        errors?.rr_number
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="rr_received_date">
                                    RR received date *
                                </Label>
                                <Input
                                    id="rr_received_date"
                                    type="date"
                                    value={form.data.rr_received_date}
                                    onChange={(e) =>
                                        form.setData(
                                            'rr_received_date',
                                            e.target.value,
                                        )
                                    }
                                    className="text-sm"
                                />
                                <InputError
                                    message={
                                        form.errors.rr_received_date ??
                                        errors?.rr_received_date
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="rr_weight_mt">
                                    Weight (MT)
                                </Label>
                                <Input
                                    id="rr_weight_mt"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={form.data.rr_weight_mt}
                                    onChange={(e) =>
                                        form.setData(
                                            'rr_weight_mt',
                                            e.target.value,
                                        )
                                    }
                                    className="text-sm"
                                />
                                <InputError
                                    message={
                                        form.errors.rr_weight_mt ??
                                        errors?.rr_weight_mt
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="fnr">FNR</Label>
                                <Input
                                    id="fnr"
                                    value={form.data.fnr}
                                    onChange={(e) =>
                                        form.setData('fnr', e.target.value)
                                    }
                                    className="text-sm"
                                />
                                <InputError
                                    message={form.errors.fnr ?? errors?.fnr}
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="from_station_code">
                                        From station code
                                    </Label>
                                    <Input
                                        id="from_station_code"
                                        value={form.data.from_station_code}
                                        onChange={(e) =>
                                            form.setData(
                                                'from_station_code',
                                                e.target.value,
                                            )
                                        }
                                        className="text-sm"
                                    />
                                    <InputError
                                        message={
                                            form.errors.from_station_code ??
                                            errors?.from_station_code
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="to_station_code">
                                        To station code
                                    </Label>
                                    <Input
                                        id="to_station_code"
                                        value={form.data.to_station_code}
                                        onChange={(e) =>
                                            form.setData(
                                                'to_station_code',
                                                e.target.value,
                                            )
                                        }
                                        className="text-sm"
                                    />
                                    <InputError
                                        message={
                                            form.errors.to_station_code ??
                                            errors?.to_station_code
                                        }
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="freight_total">
                                    Freight total (₹)
                                </Label>
                                <Input
                                    id="freight_total"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={form.data.freight_total}
                                    onChange={(e) =>
                                        form.setData(
                                            'freight_total',
                                            e.target.value,
                                        )
                                    }
                                    className="text-sm"
                                />
                                <InputError
                                    message={
                                        form.errors.freight_total ??
                                        errors?.freight_total
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="document_status">Status</Label>
                                <select
                                    id="document_status"
                                    value={form.data.document_status}
                                    onChange={(e) =>
                                        form.setData(
                                            'document_status',
                                            e.target.value,
                                        )
                                    }
                                    className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                >
                                    <option value="received">Received</option>
                                    <option value="verified">Verified</option>
                                    <option value="discrepancy">
                                        Discrepancy
                                    </option>
                                </select>
                                <InputError
                                    message={
                                        form.errors.document_status ??
                                        errors?.document_status
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="discrepancy_details">
                                    Discrepancy details
                                </Label>
                                <textarea
                                    id="discrepancy_details"
                                    rows={3}
                                    value={form.data.discrepancy_details}
                                    onChange={(e) =>
                                        form.setData(
                                            'discrepancy_details',
                                            e.target.value,
                                        )
                                    }
                                    className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                />
                                <InputError
                                    message={
                                        form.errors.discrepancy_details ??
                                        errors?.discrepancy_details
                                    }
                                />
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    Update
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
