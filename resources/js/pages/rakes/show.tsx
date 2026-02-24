import Heading from '@/components/heading';
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
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage, useForm } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Clock, FileText, Scale, Train, Edit, Package } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
    loaders?: { id: number; loader_name: string; code: string }[];
}

interface Wagon {
    id: number;
    wagon_sequence: number;
    wagon_number: string;
    wagon_type: string | null;
    tare_weight_mt: string | null;
    loaded_weight_mt: string | null;
    pcc_weight_mt: string | null;
    loader_recorded_qty_mt: string | null;
    weighment_qty_mt: string | null;
    is_unfit: boolean;
    is_overloaded: boolean;
    state: string | null;
}

interface TxrRecord {
    id: number;
    inspection_time: string;
    state: string;
    unfit_wagons_count: number;
    unfit_wagon_numbers: string | null;
    remarks: string | null;
}

interface WeighmentRecord {
    id: number;
    weighment_time: string;
    total_weight_mt: string;
    weighment_status: string | null;
    weighment_slip_url?: string | null;
}

interface GuardInspectionRecord {
    id: number;
    inspection_time: string;
    is_approved: boolean;
    remarks: string | null;
}

interface RrDocumentRecord {
    id: number;
    rr_number: string;
    rr_received_date: string;
    rr_weight_mt: string | null;
    document_status: string;
}

interface PenaltyBreakdown {
    formula?: string;
    demurrage_hours?: number;
    weight_mt?: number;
    rate_per_mt_hour?: number;
    free_hours?: number | null;
    dwell_hours?: number | null;
}

interface PenaltyRecord {
    id: number;
    penalty_type: string;
    penalty_amount: string;
    penalty_status: string;
    penalty_date: string;
    description: string | null;
    calculation_breakdown?: PenaltyBreakdown | null;
}

interface RakeData {
    id: number;
    rake_number: string;
    rake_type: string | null;
    wagon_count: number;
    state: string;
    placement_time: string | null;
    dispatch_time: string | null;
    free_time_minutes: number | null;
    siding?: Siding | null;
    wagons: Wagon[];
    txr: TxrRecord | null;
    weighments: WeighmentRecord[];
    guardInspection: GuardInspectionRecord | null;
    rr_documents?: RrDocumentRecord[];
    penalties?: PenaltyRecord[];
}

interface Props {
    rake: RakeData;
    demurrageRemainingMinutes: number | null;
    demurrage_rate_per_mt_hour: number;
}

function formatRemaining(m: number): string {
    if (m <= 0) return '0m';
    const h = Math.floor(m / 60);
    const min = m % 60;
    if (h > 0) return `${h}h ${min}m`;
    return `${min}m`;
}

function WeighmentForm({ rakeId }: { rakeId: number }) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const form = e.currentTarget;
        const formData = new FormData(form);
        router.post(`/rakes/${rakeId}/weighments`, formData, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <form
            onSubmit={handleSubmit}
            className="flex flex-wrap items-end gap-4 rounded border bg-muted/30 p-4"
        >
            <div className="grid gap-1.5">
                <Label htmlFor="weighment_time">Weighment time *</Label>
                <Input
                    id="weighment_time"
                    name="weighment_time"
                    type="datetime-local"
                    required
                    className="w-48 text-sm"
                />
                <InputError message={errors?.weighment_time} />
            </div>
            <div className="grid gap-1.5">
                <Label htmlFor="total_weight_mt">Total weight (MT) *</Label>
                <Input
                    id="total_weight_mt"
                    name="total_weight_mt"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    className="w-28 text-sm"
                />
                <InputError message={errors?.total_weight_mt} />
            </div>
            <div className="grid gap-1.5">
                <Label htmlFor="weighment_pdf">Weighment slip (PDF)</Label>
                <Input
                    id="weighment_pdf"
                    name="pdf"
                    type="file"
                    accept=".pdf,application/pdf"
                    className="w-48 text-sm"
                />
                <InputError message={errors?.pdf} />
            </div>
            <Button type="submit" size="sm">
                Record weighment
            </Button>
        </form>
    );
}

export default function RakesShow({
    rake,
    demurrageRemainingMinutes,
    demurrage_rate_per_mt_hour,
}: Props) {
    const [selectedWagon, setSelectedWagon] = useState<Wagon | null>(null);
    const [wagonDialogOpen, setWagonDialogOpen] = useState(false);
    const { data, setData, put, processing, errors, reset } = useForm({
        wagon_type: '',
        is_unfit: false,
        tare_weight_mt: '',
        loaded_weight_mt: '',
        pcc_weight_mt: '',
        loader_recorded_qty_mt: '',
        weighment_qty_mt: '',
    });

    useEffect(() => {
        if (rake.wagons.length === 0 && rake.wagon_count > 0) {
            router.visit(`/rakes/${rake.id}/edit`);
        }
    }, [rake.id, rake.wagons.length, rake.wagon_count]);

    useEffect(() => {
        if (selectedWagon) {
            setData((prev) => ({
                ...prev,
                wagon_type: selectedWagon.wagon_type ?? '',
                tare_weight_mt: selectedWagon.tare_weight_mt ?? '',
                pcc_weight_mt: selectedWagon.pcc_weight_mt ?? '',
                is_unfit: selectedWagon.is_unfit ?? false,
            }));
        }
    }, [selectedWagon, setData]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Rakes', href: '/rakes' },
        { title: rake.rake_number, href: `/rakes/${rake.id}` },
    ];
    const isLow =
        demurrageRemainingMinutes !== null && demurrageRemainingMinutes <= 30;
    const isCritical =
        demurrageRemainingMinutes !== null && demurrageRemainingMinutes <= 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Rake ${rake.rake_number}`} />
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title={`Rake ${rake.rake_number}`}
                        description={
                            rake.siding
                                ? `${rake.siding.name} (${rake.siding.code})`
                                : 'Railway rake detail'
                        }
                    />
                    <Link
                        href="/rakes"
                        className="text-sm font-medium text-muted-foreground underline underline-offset-4"
                    >
                        ← Back to list
                    </Link>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>State</CardDescription>
                            <CardTitle className="text-lg capitalize">
                                {rake.state}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Wagons</CardDescription>
                            <CardTitle className="text-lg">
                                {rake.wagon_count}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    {demurrageRemainingMinutes !== null && (
                        <Card
                            className={
                                isCritical
                                    ? 'border-red-500'
                                    : isLow
                                      ? 'border-amber-500'
                                      : ''
                            }
                        >
                            <CardHeader className="pb-2">
                                <CardDescription className="flex items-center gap-1">
                                    <Clock className="size-4" />
                                    Demurrage remaining
                                </CardDescription>
                                <CardTitle
                                    className={
                                        'text-lg ' +
                                        (isCritical
                                            ? 'text-red-600 dark:text-red-400'
                                            : isLow
                                              ? 'text-amber-600 dark:text-amber-400'
                                              : '')
                                    }
                                >
                                    {formatRemaining(demurrageRemainingMinutes)}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                    )}
                </div>

                {rake.txr && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Train className="size-5" />
                                TXR (Train Examination Report)
                            </CardTitle>
                            <CardDescription>
                                Inspection time and unfit wagons
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <p>
                                <span className="text-muted-foreground">
                                    Inspection:
                                </span>{' '}
                                {new Date(
                                    rake.txr.inspection_time,
                                ).toLocaleString()}
                            </p>
                            <p>
                                <span className="text-muted-foreground">
                                    State:
                                </span>{' '}
                                {rake.txr.state}
                            </p>
                            <p>
                                <span className="text-muted-foreground">
                                    Unfit wagons:
                                </span>{' '}
                                {rake.txr.unfit_wagons_count}
                                {rake.txr.unfit_wagon_numbers
                                    ? ` (${rake.txr.unfit_wagon_numbers})`
                                    : ''}
                            </p>
                            {rake.txr.remarks && (
                                <p>
                                    <span className="text-muted-foreground">
                                        Remarks:
                                    </span>{' '}
                                    {rake.txr.remarks}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}

                {rake.wagons.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Wagons</CardTitle>
                            <CardDescription>
                                Click on any wagon to upload details
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                                {rake.wagons.map((wagon) => (
                                    <Dialog 
                                        key={wagon.id}
                                        open={wagonDialogOpen && selectedWagon?.id === wagon.id}
                                        onOpenChange={(open) => {
                                            setWagonDialogOpen(open);
                                            if (!open) setSelectedWagon(null);
                                        }}
                                    >
                                        <DialogTrigger asChild>
                                            <div 
                                                className={`cursor-pointer rounded-lg border-2 p-6 hover:bg-muted/50 transition-colors ${
                                                    wagon.is_unfit 
                                                        ? 'border-red-500 bg-red-50/50' 
                                                        : 'border-green-500 bg-green-50/50'
                                                }`}
                                                onClick={() => {
                                                    setSelectedWagon(wagon);
                                                    setWagonDialogOpen(true);
                                                }}
                                            >
                                                <div className="text-center space-y-3">
                                                    <Package className="mx-auto h-12 w-12 text-muted-foreground" />
                                                    <div>
                                                        <div className="font-semibold text-lg">
                                                            {wagon.wagon_number}
                                                        </div>
                                                        <div className="text-sm text-muted-foreground">
                                                            Position {wagon.wagon_sequence}
                                                        </div>
                                                    </div>
                                                    <div className="space-y-1 text-xs">
                                                        <div className="flex justify-between">
                                                            <span className="text-muted-foreground">Type:</span>
                                                            <span className="font-medium">
                                                                {wagon.wagon_type || 'Not set'}
                                                            </span>
                                                        </div>
                                                        <div className="flex justify-between">
                                                            <span className="text-muted-foreground">Tare Weight:</span>
                                                            <span className="font-medium">
                                                                {wagon.tare_weight_mt ? `${wagon.tare_weight_mt} MT` : 'Not set'}
                                                            </span>
                                                        </div>
                                                        <div className="flex justify-between">
                                                            <span className="text-muted-foreground">PCC Weight:</span>
                                                            <span className="font-medium">
                                                                {wagon.pcc_weight_mt ? `${wagon.pcc_weight_mt} MT` : 'Not set'}
                                                            </span>
                                                        </div>
                                                        <div className="flex justify-between">
                                                            <span className="text-muted-foreground">Status:</span>
                                                            <span className={`font-medium ${
                                                                wagon.is_unfit ? 'text-red-600' : 'text-green-600'
                                                            }`}>
                                                                {wagon.is_unfit ? 'Unfit' : 'Fit'}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </DialogTrigger>
                                        <DialogContent className="max-w-md">
                                            <DialogHeader>
                                                <DialogTitle>
                                                    Edit Wagon {wagon.wagon_number}
                                                </DialogTitle>
                                            </DialogHeader>
                                            <form onSubmit={(e) => {
                                                e.preventDefault();
                                                put(`/rakes/${rake.id}/wagons/${wagon.id}`, {
                                                    preserveScroll: true,
                                                });
                                            }}>
                                                <div className="space-y-4">
                                                    <div>
                                                        <Label htmlFor="wagon_type">Wagon Type</Label>
                                                        <Input
                                                            id="wagon_type"
                                                            value={data.wagon_type}
                                                            onChange={(e) => setData('wagon_type', e.target.value)}
                                                            placeholder="e.g., BOXN, BOBRN"
                                                        />
                                                        <InputError message={errors?.wagon_type} />
                                                    </div>
                                                    <div>
                                                        <Label htmlFor="tare_weight_mt">Tare Weight (MT)</Label>
                                                        <Input
                                                            id="tare_weight_mt"
                                                            value={data.tare_weight_mt}
                                                            onChange={(e) => setData('tare_weight_mt', e.target.value)}
                                                            type="number"
                                                            step="0.01"
                                                        />
                                                        <InputError message={errors?.tare_weight_mt} />
                                                    </div>
                                                    <div>
                                                        <Label htmlFor="pcc_weight_mt">PCC Weight (MT)</Label>
                                                        <Input
                                                            id="pcc_weight_mt"
                                                            value={data.pcc_weight_mt}
                                                            onChange={(e) => setData('pcc_weight_mt', e.target.value)}
                                                            type="number"
                                                            step="0.01"
                                                        />
                                                        <InputError message={errors?.pcc_weight_mt} />
                                                    </div>
                                                    <div className="flex items-center space-x-2">
                                                        <input
                                                            type="checkbox"
                                                            id="is_unfit"
                                                            checked={data.is_unfit}
                                                            onChange={(e) => setData('is_unfit', e.target.checked)}
                                                            className="rounded"
                                                        />
                                                        <Label htmlFor="is_unfit">Mark as unfit</Label>
                                                    </div>
                                                    <div className="flex justify-end space-x-2 pt-4">
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            onClick={() => {
                                                                setWagonDialogOpen(false);
                                                                setSelectedWagon(null);
                                                                reset();
                                                            }}
                                                        >
                                                            Cancel
                                                        </Button>
                                                        <Button type="submit" disabled={processing}>
                                                            Save Changes
                                                        </Button>
                                                    </div>
                                                </div>
                                            </form>
                                        </DialogContent>
                                    </Dialog>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {rake.wagons.length === 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>No Wagons</CardTitle>
                            <CardDescription>
                                This rake doesn't have any wagons yet. Generate wagons based on the rake's wagon count.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-8 text-center">
                            <Button 
                                onClick={() => {
                                    router.post(`/rakes/${rake.id}/generate-wagons`, {}, {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            router.reload();
                                        },
                                        onError: (errors) => {
                                            console.error('Error generating wagons:', errors);
                                        },
                                    });
                                }}
                                disabled={processing}
                            >
                                <Package className="mr-2 h-4 w-4" />
                                Generate {rake.wagon_count} Wagons
                            </Button>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Weighments</CardTitle>
                        <CardDescription>
                            Weighment records for this rake. Attach weighment
                            slip PDF when recording.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {rake.weighments.length > 0 && (
                            <ul className="space-y-2 text-sm">
                                {rake.weighments.map((w) => (
                                    <li
                                        key={w.id}
                                        className="flex flex-wrap items-center gap-4 rounded border p-3"
                                    >
                                        <span>
                                            {new Date(
                                                w.weighment_time,
                                            ).toLocaleString()}
                                        </span>
                                        <span>
                                            Total: {w.total_weight_mt} MT
                                        </span>
                                        {w.weighment_status && (
                                            <span className="capitalize">
                                                {w.weighment_status}
                                            </span>
                                        )}
                                        {w.weighment_slip_url && (
                                            <a
                                                href={w.weighment_slip_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-primary underline underline-offset-2"
                                            >
                                                View slip
                                            </a>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                        <WeighmentForm rakeId={rake.id} />
                    </CardContent>
                </Card>
                {rake.guardInspection?.inspection_time && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Guard inspection</CardTitle>
                            <CardDescription>
                                Guard approval status
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="text-sm">
                            <p>
                                {new Date(
                                    rake.guardInspection?.inspection_time || '',
                                ).toLocaleString()}{' '}
                                —{' '}
                                {rake.guardInspection?.is_approved
                                    ? 'Approved'
                                    : 'Not approved'}
                            </p>
                            {rake.guardInspection?.remarks && (
                                <p className="mt-2 text-muted-foreground">
                                    {rake.guardInspection.remarks}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}

                {(rake.penalties?.length ?? 0) > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Scale className="size-5" />
                                Penalties
                            </CardTitle>
                            <CardDescription>
                                Demurrage formula: hours over free time × weight
                                (MT) × ₹{demurrage_rate_per_mt_hour}/MT/h
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ul className="space-y-3 text-sm">
                                {rake.penalties?.map((p) => (
                                    <li
                                        key={p.id}
                                        className="rounded border p-3"
                                    >
                                        <div className="flex flex-wrap items-center gap-4">
                                            <span className="font-medium">
                                                {p.penalty_type}
                                            </span>
                                            <span>₹{p.penalty_amount}</span>
                                            <span className="text-muted-foreground">
                                                {p.penalty_status} ·{' '}
                                                {p.penalty_date}
                                            </span>
                                        </div>
                                        {p.description && (
                                            <p className="mt-2 text-muted-foreground">
                                                {p.description}
                                            </p>
                                        )}
                                        {p.calculation_breakdown && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {
                                                    p.calculation_breakdown
                                                        .formula
                                                }
                                                {p.calculation_breakdown
                                                    .demurrage_hours !=
                                                    null && (
                                                    <>
                                                        {' '}
                                                        ={' '}
                                                        {
                                                            p
                                                                .calculation_breakdown
                                                                .demurrage_hours
                                                        }{' '}
                                                        h ×{' '}
                                                        {
                                                            p
                                                                .calculation_breakdown
                                                                .weight_mt
                                                        }{' '}
                                                        MT × ₹
                                                        {
                                                            p
                                                                .calculation_breakdown
                                                                .rate_per_mt_hour
                                                        }
                                                        /MT/h
                                                    </>
                                                )}
                                            </p>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="size-5" />
                            Railway Receipts (RR)
                        </CardTitle>
                        <CardDescription>
                            RR documents linked to this rake
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {(rake.rr_documents?.length ?? 0) > 0 ? (
                            <ul className="space-y-2 text-sm">
                                {rake.rr_documents?.map((rr) => (
                                    <li
                                        key={rr.id}
                                        className="flex flex-wrap items-center gap-4 rounded border p-3"
                                    >
                                        <Link
                                            href={`/railway-receipts/${rr.id}`}
                                            className="font-medium underline underline-offset-2"
                                        >
                                            {rr.rr_number}
                                        </Link>
                                        <span className="text-muted-foreground">
                                            {rr.rr_received_date}
                                        </span>
                                        {rr.rr_weight_mt != null && (
                                            <span>{rr.rr_weight_mt} MT</span>
                                        )}
                                        <span className="capitalize">
                                            {rr.document_status}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                No RR documents for this rake.
                            </p>
                        )}
                        <div className="mt-3">
                            <Link
                                href={`/railway-receipts/create?rake_id=${rake.id}`}
                            >
                                <Button variant="outline" size="sm">
                                    Add RR document
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                {!rake.txr && rake.wagons.length === 0 && (
                    <Card>
                        <CardContent className="p-8 text-center text-sm text-muted-foreground">
                            No TXR or wagon data yet. Use the rail dispatch
                            flows to record TXR, loading, and weighment.
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
