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
import { Clock, FileText, Scale, Train, Edit, Trash2 } from 'lucide-react';
import { RakeWorkflow } from '@/components/rakes/workflow/RakeWorkflow';
import { WagonOverviewDialog } from '@/components/rakes/WagonOverviewDialog';

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
    inspection_end_time?: string | null;
    status: string;
    remarks: string | null;
}

interface RakeWagonLoading {
    id: number;
    wagon_id: number;
    loader_id: number | null;
    loaded_quantity_mt: string;
    wagon?: {
        id: number;
        wagon_number: string;
        wagon_sequence: number;
    };
    loader?: {
        id: number;
        loader_name: string;
        code: string;
    };
}

interface GuardInspectionRecord {
    id: number;
    inspection_time: string;
    movement_permission_time: string;
    is_approved: boolean;
    remarks: string | null;
}

interface WeighmentRecord {
    id: number;
    weighment_time: string;
    total_weight_mt: string;
    status: string | null;
    train_speed_kmph: number;
    attempt_no: number;
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
    wagon_count: number | null;
    state: string;
    placement_time: string | null;
    dispatch_time: string | null;
    loading_free_minutes: number | null;
    siding?: Siding | null;
    wagons: Wagon[];
    txr: TxrRecord | null;
    wagonLoadings?: RakeWagonLoading[];
    guardInspections?: GuardInspectionRecord[];
    weighments?: WeighmentRecord[];
    penalties?: PenaltyRecord[];
}

interface Props {
    rake: RakeData;
    demurrageRemainingMinutes: number | null;
    demurrage_rate_per_mt_hour: number;
}

function formatRemaining(m: number): string {
    if (m <= 0) return '0m 0s';
    const h = Math.floor(m / 60);
    const min = m % 60;
    if (h > 0) return `${h}h ${min}m 0s`;
    return `${min}m 0s`;
}

function formatRemainingWithSeconds(totalSeconds: number): string {
    if (totalSeconds <= 0) return '0m 0s';
    const h = Math.floor(totalSeconds / 3600);
    const min = Math.floor((totalSeconds % 3600) / 60);
    const sec = totalSeconds % 60;
    if (h > 0) return `${h}h ${min}m ${sec}s`;
    return `${min}m ${sec}s`;
}

function formatDemurrageTime(elapsedMinutes: number, freeTimeMinutes: number): string {
    if (elapsedMinutes <= freeTimeMinutes) return '0h 0m';
    const extraMinutes = elapsedMinutes - freeTimeMinutes;
    const h = Math.floor(extraMinutes / 60);
    const min = extraMinutes % 60;
    return `${h}h ${min}m`;
}

function LoadingTimerCard({ load }: { load: RakeLoad }) {
    const [elapsedSeconds, setElapsedSeconds] = useState(() =>
        Math.floor((Date.now() - new Date(load.placement_time).getTime()) / 1000)
    );
    
    const elapsedMinutes = Math.floor(elapsedSeconds / 60);
    const remainingSeconds = Math.max(0, (load.free_time_minutes * 60) - elapsedSeconds);
    const remainingMinutes = Math.ceil(remainingSeconds / 60);
    const isOverFreeTime = elapsedSeconds > (load.free_time_minutes * 60);
    const isLast30Minutes = !isOverFreeTime && remainingMinutes <= 30;
    const demurrageTime = formatDemurrageTime(elapsedMinutes, load.free_time_minutes);

    useEffect(() => {
        if (load.status === 'completed') return;
        const interval = setInterval(() => {
            setElapsedSeconds(
                Math.floor((Date.now() - new Date(load.placement_time).getTime()) / 1000)
            );
        }, 1000);
        return () => clearInterval(interval);
    }, [load.placement_time, load.status]);

    return (
        <Card
            className={
                load.status === 'completed'
                    ? 'border-green-500'
                    : isOverFreeTime
                    ? 'border-orange-500'
                    : isLast30Minutes
                    ? 'border-red-500'
                    : 'border-blue-500'
            }
        >
            <CardHeader className="pb-2">
                <CardDescription className="flex items-center gap-1">
                    <Clock className="size-4" />
                    Loading Timer
                </CardDescription>
                <CardTitle
                    className={
                        'text-lg ' +
                        (load.status === 'completed'
                            ? 'text-green-600 dark:text-green-400'
                            : isOverFreeTime
                            ? 'text-orange-600 dark:text-orange-400'
                            : isLast30Minutes
                            ? 'text-red-600 dark:text-red-400'
                            : 'text-blue-600 dark:text-blue-400')
                    }
                >
                    {load.status === 'completed'
                        ? 'Completed'
                        : isOverFreeTime
                        ? 'Demurrage Time'
                        : formatRemainingWithSeconds(remainingSeconds) + ' remaining'}
                </CardTitle>
            </CardHeader>
            <CardContent className="text-xs text-muted-foreground space-y-1">
                <p>Started: {new Date(load.placement_time).toLocaleString()}</p>
                <p>Free time: {load.free_time_minutes} minutes</p>
                {load.status === 'completed' && (
                    <p className="text-green-600">Loading completed successfully</p>
                )}
                {load.status !== 'completed' && (
                    <>
                        <p className={isOverFreeTime ? 'text-orange-600' : isLast30Minutes ? 'text-red-600' : ''}>
                            {isOverFreeTime ? 'Free time exceeded' : isLast30Minutes ? '⚠️ Less than 30 minutes!' : `Time remaining: ${formatRemainingWithSeconds(remainingSeconds)}`}
                        </p>
                        {isLast30Minutes && (
                            <div className="mt-2 p-2 bg-red-50 border border-red-200 rounded text-red-700">
                                <div className="font-medium text-sm">⚠️ Warning: Less than 30 minutes remaining!</div>
                                <div className="text-xs mt-1">Complete loading soon to avoid demurrage charges</div>
                            </div>
                        )}
                        {isOverFreeTime && (
                            <div className="mt-2 p-2 bg-orange-50 border border-orange-200 rounded text-orange-700">
                                <div className="font-medium">Demurrage: {demurrageTime}</div>
                                <div className="text-xs">Extra charges apply</div>
                            </div>
                        )}
                        <p>Total elapsed: {formatRemainingWithSeconds(elapsedSeconds)}</p>
                    </>
                )}
            </CardContent>
        </Card>
    );
}

function getUnfitWagonInfo(wagons: Wagon[]): { count: number; numbers: string } {
    const unfitWagons = wagons.filter(wagon => wagon.is_unfit);
    const count = unfitWagons.length;
    const numbers = unfitWagons.map(wagon => wagon.wagon_number).join(', ');
    return { count, numbers };
}

function WagonLoadingForm({ rake }: { rake: RakeData }) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const { data, setData, post, processing, reset } = useForm({
        wagon_id: '',
        loader_id: '',
        loaded_quantity_mt: '',
    });

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(`/rakes/${rake.id}/load/wagon`, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
            },
        });
    };

    // Get unloaded wagons
    const unloadedWagons = rake.wagons.filter(wagon => 
        !rake.wagonLoadings?.some(loading => loading.wagon_id === wagon.id)
    );

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div>
                <Label htmlFor="wagon_id">Select Wagon</Label>
                <select
                    id="wagon_id"
                    name="wagon_id"
                    value={data.wagon_id}
                    onChange={(e) => setData('wagon_id', e.target.value)}
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    required
                >
                    <option value="">Select a wagon</option>
                    {unloadedWagons.map((wagon) => (
                        <option key={wagon.id} value={wagon.id}>
                            {wagon.wagon_number} (Position {wagon.wagon_sequence})
                        </option>
                    ))}
                </select>
                <InputError message={errors?.wagon_id} />
            </div>
            <div>
                <Label htmlFor="loader_id">Select Loader</Label>
                <select
                    id="loader_id"
                    name="loader_id"
                    value={data.loader_id}
                    onChange={(e) => setData('loader_id', e.target.value)}
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    required
                >
                    <option value="">Select a loader</option>
                    {rake.siding?.loaders?.map((loader) => (
                        <option key={loader.id} value={loader.id}>
                            {loader.loader_name} ({loader.code})
                        </option>
                    ))}
                </select>
                <InputError message={errors?.loader_id} />
            </div>
            <div>
                <Label htmlFor="loaded_quantity_mt">Loaded Quantity (MT)</Label>
                <Input
                    id="loaded_quantity_mt"
                    name="loaded_quantity_mt"
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.loaded_quantity_mt}
                    onChange={(e) => setData('loaded_quantity_mt', e.target.value)}
                    required
                />
                <InputError message={errors?.loaded_quantity_mt} />
            </div>
            <div className="flex justify-end space-x-2 pt-4">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => reset()}
                >
                    Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                    Load Wagon
                </Button>
            </div>
        </form>
    );
}

function GuardInspectionForm({ rake }: { rake: RakeData }) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const { data, setData, post, processing, reset } = useForm({
        inspection_time: new Date().toISOString().slice(0, 16),
        movement_permission_time: new Date().toISOString().slice(0, 16),
        is_approved: false,
        remarks: '',
    });

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(`/rakes/${rake.id}/load/guard-inspection`, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
            },
        });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div>
                <Label htmlFor="inspection_time">Inspection Time</Label>
                <Input
                    id="inspection_time"
                    name="inspection_time"
                    type="datetime-local"
                    value={data.inspection_time}
                    onChange={(e) => setData('inspection_time', e.target.value)}
                    required
                />
                <InputError message={errors?.inspection_time} />
            </div>
            <div>
                <Label htmlFor="movement_permission_time">Movement Permission Time</Label>
                <Input
                    id="movement_permission_time"
                    name="movement_permission_time"
                    type="datetime-local"
                    value={data.movement_permission_time}
                    onChange={(e) => setData('movement_permission_time', e.target.value)}
                    required
                />
                <InputError message={errors?.movement_permission_time} />
            </div>
            <div className="flex items-center space-x-2">
                <input
                    type="checkbox"
                    id="is_approved"
                    checked={data.is_approved}
                    onChange={(e) => setData('is_approved', e.target.checked)}
                    className="rounded"
                />
                <Label htmlFor="is_approved">Approved for movement</Label>
            </div>
            <div>
                <Label htmlFor="remarks">Remarks</Label>
                <textarea
                    id="remarks"
                    name="remarks"
                    value={data.remarks}
                    onChange={(e) => setData('remarks', e.target.value)}
                    rows={3}
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    placeholder="Any inspection remarks..."
                />
                <InputError message={errors?.remarks} />
            </div>
            <div className="flex justify-end space-x-2 pt-4">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => reset()}
                >
                    Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                    Record Inspection
                </Button>
            </div>
        </form>
    );
}

function InMotionWeighmentForm({ rake }: { rake: RakeData }) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const { data, setData, post, processing, reset } = useForm({
        train_speed_kmph: '',
        wagon_weights: [] as Array<{ wagon_id: number; gross_weight_mt: string }>,
    });

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(`/rakes/${rake.id}/load/weighment`, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
            },
        });
    };

    const handleWagonWeightChange = (wagonId: number, weight: string) => {
        const newWeights = data.wagon_weights.filter(w => w.wagon_id !== wagonId);
        if (weight) {
            newWeights.push({ wagon_id: wagonId, gross_weight_mt: weight });
        }
        setData('wagon_weights', newWeights);
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div>
                <Label htmlFor="train_speed_kmph">Train Speed (km/h) - Must be 5-7 km/h</Label>
                <Input
                    id="train_speed_kmph"
                    name="train_speed_kmph"
                    type="number"
                    step="0.1"
                    min="5"
                    max="7"
                    value={data.train_speed_kmph}
                    onChange={(e) => setData('train_speed_kmph', e.target.value)}
                    required
                />
                <InputError message={errors?.train_speed_kmph} />
            </div>
            
            <div>
                <Label>Wagon Weights (MT)</Label>
                <div className="space-y-2 max-h-60 overflow-y-auto border rounded-lg p-3">
                    {rake.wagons.map((wagon) => (
                        <div key={wagon.id} className="flex items-center gap-3">
                            <span className="text-sm font-medium w-24">
                                {wagon.wagon_number}
                            </span>
                            <Input
                                placeholder="Gross weight"
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.wagon_weights.find(w => w.wagon_id === wagon.id)?.gross_weight_mt || ''}
                                onChange={(e) => handleWagonWeightChange(wagon.id, e.target.value)}
                                className="flex-1"
                                required
                            />
                            {wagon.pcc_weight_mt && (
                                <span className="text-xs text-muted-foreground w-20">
                                    PCC: {wagon.pcc_weight_mt} MT
                                </span>
                            )}
                        </div>
                    ))}
                </div>
                <InputError message={errors?.wagon_weights} />
            </div>
            
            <div className="flex justify-end space-x-2 pt-4">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => reset()}
                >
                    Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                    Record Weighment
                </Button>
            </div>
        </form>
    );
}

function TxrEndForm({ rake }: { rake: RakeData }) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const { data, setData, post, processing, reset } = useForm({
        remarks: '',
    });

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('remarks', data.remarks);
        
        post(`/rakes/${rake.id}/txr/end`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
            },
        });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div>
                <Label htmlFor="remarks">Remarks</Label>
                <textarea
                    id="remarks"
                    name="remarks"
                    value={data.remarks}
                    onChange={(e) => setData('remarks', e.target.value)}
                    rows={3}
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    placeholder="Any additional remarks..."
                />
                <InputError message={errors?.remarks} />
            </div>
            <div className="flex justify-end space-x-2 pt-4">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => reset()}
                >
                    Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                    End TXR
                </Button>
            </div>
        </form>
    );
}

function TxrEditForm({ rake }: { rake: RakeData }) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const { data, setData, put, processing, reset } = useForm({
        inspection_time: rake.txr?.inspection_time ? new Date(rake.txr.inspection_time).toISOString().slice(0, 16) : '',
        inspection_end_time: rake.txr?.inspection_end_time ? new Date(rake.txr.inspection_end_time).toISOString().slice(0, 16) : '',
        status: rake.txr?.status || 'completed',
        remarks: rake.txr?.remarks || '',
    });

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        put(`/rakes/${rake.id}/txr`, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
            },
        });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div>
                <Label htmlFor="inspection_time">Inspection Start Time</Label>
                <Input
                    id="inspection_time"
                    name="inspection_time"
                    type="datetime-local"
                    value={data.inspection_time}
                    onChange={(e) => setData('inspection_time', e.target.value)}
                    required
                />
                <InputError message={errors?.inspection_time} />
            </div>
            <div>
                <Label htmlFor="inspection_end_time">Inspection End Time</Label>
                <Input
                    id="inspection_end_time"
                    name="inspection_end_time"
                    type="datetime-local"
                    value={data.inspection_end_time}
                    onChange={(e) => setData('inspection_end_time', e.target.value)}
                />
                <InputError message={errors?.inspection_end_time} />
            </div>
            <div>
                <Label htmlFor="status">Status</Label>
                <select
                    id="status"
                    name="status"
                    value={data.status}
                    onChange={(e) => setData('status', e.target.value)}
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    required
                >
                    <option value="completed">Completed</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <InputError message={errors?.status} />
            </div>
            <div>
                <Label htmlFor="remarks">Remarks</Label>
                <textarea
                    id="remarks"
                    name="remarks"
                    value={data.remarks}
                    onChange={(e) => setData('remarks', e.target.value)}
                    rows={3}
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    placeholder="Any additional remarks..."
                />
                <InputError message={errors?.remarks} />
            </div>
            <div className="flex justify-end space-x-2 pt-4">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => reset()}
                >
                    Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                    Save Changes
                </Button>
            </div>
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

    // Calculate unfit wagon information
    const unfitWagonInfo = getUnfitWagonInfo(rake.wagons);

    useEffect(() => {
        if (rake.wagons.length === 0 && (rake.wagon_count ?? 0) > 0) {
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
                    <div className="flex gap-2">
                        <WagonOverviewDialog wagons={rake.wagons} />
                        <Link
                            href="/rakes"
                            className="text-sm font-medium text-muted-foreground underline underline-offset-4"
                        >
                            ← Back to list
                        </Link>
                    </div>
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
                                {rake.wagon_count ?? 0}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    {/* Loading timer when placement_time and loading_free_minutes exist on Rake */}
                    {rake.placement_time && rake.loading_free_minutes != null && (
                        <LoadingTimerCard
                            load={{
                                placement_time: rake.placement_time,
                                free_time_minutes: rake.loading_free_minutes,
                                status: rake.state === 'ready_for_dispatch' ? 'completed' : 'in_progress',
                            }}
                        />
                    )}
                </div>

                {rake.txr ? (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Train className="size-5" />
                                    TXR (Train Examination Report)
                                </div>
                                <div className="flex gap-2">
                                    {rake.txr.status === 'in_progress' && (
                                        <Dialog>
                                            <DialogTrigger asChild>
                                                <Button size="sm" variant="destructive">
                                                    End TXR
                                                </Button>
                                            </DialogTrigger>
                                            <DialogContent className="max-w-md">
                                                <DialogHeader>
                                                    <DialogTitle>End TXR</DialogTitle>
                                                </DialogHeader>
                                                <TxrEndForm rake={rake} />
                                            </DialogContent>
                                        </Dialog>
                                    )}
                                    {rake.txr.status === 'completed' && (
                                        <Dialog>
                                            <DialogTrigger asChild>
                                                <Button size="sm" variant="outline">
                                                    <Edit className="mr-2 h-4 w-4" />
                                                    Edit TXR
                                                </Button>
                                            </DialogTrigger>
                                            <DialogContent className="max-w-md">
                                                <DialogHeader>
                                                    <DialogTitle>Edit TXR</DialogTitle>
                                                </DialogHeader>
                                                <TxrEditForm rake={rake} />
                                            </DialogContent>
                                        </Dialog>
                                    )}
                                </div>
                            </CardTitle>
                            <CardDescription>
                                Inspection time and unfit wagons
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <p>
                                <span className="text-muted-foreground">
                                    Status:
                                </span>{' '}
                                <span className={`capitalize font-medium ${
                                    rake.txr.status === 'in_progress' ? 'text-blue-600' :
                                    rake.txr.status === 'completed' ? 'text-green-600' :
                                    'text-gray-600'
                                }`}>
                                    {rake.txr.status.replace('_', ' ')}
                                </span>
                            </p>
                            <p>
                                <span className="text-muted-foreground">
                                    Inspection:
                                </span>{' '}
                                {new Date(
                                    rake.txr.inspection_time,
                                ).toLocaleString()}
                            </p>
                            {rake.txr.inspection_end_time && (
                                <p>
                                    <span className="text-muted-foreground">
                                        Inspection End:
                                    </span>{' '}
                                    {new Date(
                                        rake.txr.inspection_end_time,
                                    ).toLocaleString()}
                                </p>
                            )}
                            <p>
                                <span className="text-muted-foreground">
                                    Unfit wagons:
                                </span>{' '}
                                {unfitWagonInfo.count}
                                {unfitWagonInfo.numbers
                                    ? ` (${unfitWagonInfo.numbers})`
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
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Train className="size-5" />
                                TXR (Train Examination Report)
                            </CardTitle>
                            <CardDescription>
                                Start train examination for this rake
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-8 text-center">
                            <Button 
                                onClick={() => {
                                    router.post(`/rakes/${rake.id}/txr/start`, {}, {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            router.reload();
                                        },
                                        onError: (errors) => {
                                            console.error('Error starting TXR:', errors);
                                        },
                                    });
                                }}
                                disabled={false}
                            >
                                <Train className="mr-2 h-4 w-4" />
                                Start TXR
                            </Button>
                        </CardContent>
                    </Card>
                )}

                {/* New Accordion Workflow */}
                <RakeWorkflow rake={rake} demurrage_rate_per_mt_hour={demurrage_rate_per_mt_hour} />

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
