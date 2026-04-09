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
import { useEffect, useMemo, useState } from 'react';
import { Clock, FileText, Scale, Train, Edit, Trash2 } from 'lucide-react';
import { RakeWorkflow } from '@/components/rakes/workflow/RakeWorkflow';
import { WagonOverviewDialog } from '@/components/rakes/WagonOverviewDialog';
import { EditWagonsDialog } from '@/components/rakes/EditWagonsDialog';

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
    total_weight_mt: string | number | null;
    status: string | null;
    train_speed_kmph: number | string | null;
    attempt_no: number;
    wagonWeights?: Array<{
        wagon_id: number;
        gross_weight_mt: string | number | null;
        net_weight_mt: string | number | null;
        wagon: {
            id: number;
            wagon_number: string;
            wagon_sequence: number;
            pcc_weight_mt: string | number | null;
        } | null;
    }>;
}

interface RrDocumentRecord {
    id: number;
    rr_number: string;
    rr_received_date: string;
    rr_weight_mt: string | null;
    document_status: string;
    diverrt_destination_id?: number | null;
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

interface AppliedPenaltyRecord {
    id: number;
    amount: string | number;
    quantity?: string | number | null;
    wagon_id?: number | null;
    penalty_type?: { id: number; code: string; name: string; calculation_type: string };
    wagon?: { id: number; wagon_number: string; overload_weight_mt?: string | number | null };
}

interface RakeChargeRecord {
    id: number;
    charge_type: string;
    is_actual_charges: boolean;
    amount: string | number;
    appliedPenalties?: Array<{ amount: string | number }>;
    rrPenaltySnapshots?: Array<{ amount: string | number }>;
}

interface RakeData {
    id: number;
    rake_number: string;
    rake_type: string | null;
    wagon_count: number | null;
    state: string;
    placement_time: string | null;
    dispatch_time: string | null;
    rr_expected_date?: string | null;
    remarks?: string | null;
    destination?: string | null;
    loading_date?: string | null;
    loading_free_minutes: number | null;
    is_diverted?: boolean;
    destination_code?: string | null;
    rakeCharges?: RakeChargeRecord[];
    powerPlantReceipts?: Array<{
        id: number;
        power_plant_id: number;
        receipt_date: string | null;
        weight_mt: string | number;
        rr_reference: string | null;
        status: string;
        file_url: string | null;
        file_name?: string | null;
        powerPlant?: { id: number; name: string; code: string } | null;
    }>;
    siding?: Siding | null;
    wagons: Wagon[];
    txr: TxrRecord | null;
    wagonLoadings?: RakeWagonLoading[];
    guardInspections?: GuardInspectionRecord[];
    weighments?: WeighmentRecord[];
    rrDocuments?: RrDocumentRecord[];
    diverrtDestinations?: Array<{ id: number; location: string }>;
    penalties?: PenaltyRecord[];
    appliedPenalties?: AppliedPenaltyRecord[];
}

interface Props {
    rake: RakeData;
    powerPlants: Array<{
        id: number;
        name: string;
        code: string;
    }>;
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
    powerPlants,
    demurrageRemainingMinutes,
    demurrage_rate_per_mt_hour,
}: Props) {
    const [selectedWagon, setSelectedWagon] = useState<Wagon | null>(null);
    const [wagonDialogOpen, setWagonDialogOpen] = useState(false);
    const [wagons, setWagons] = useState(rake.wagons);
    const [editRakeOpen, setEditRakeOpen] = useState(false);
    const {
        data: editData,
        setData: setEditData,
        put: putEdit,
        processing: editProcessing,
        errors: editErrors,
    } = useForm({
        rake_number: rake.rake_number ?? '',
        rake_type: rake.rake_type ?? '',
        dispatch_time: rake.dispatch_time ? new Date(rake.dispatch_time).toISOString().slice(0, 16) : '',
        status: rake.state ?? 'pending',
        rr_expected_date: rake.rr_expected_date ? new Date(rake.rr_expected_date).toISOString().slice(0, 10) : '',
        placement_time: rake.placement_time ? new Date(rake.placement_time).toISOString().slice(0, 10) : '',
        loading_date:
            rake.loading_date != null && String(rake.loading_date).length > 0
                ? String(rake.loading_date).slice(0, 10)
                : '',
        destination_code: rake.destination_code ?? '',
        remarks: rake.remarks ?? '',
    });

    useEffect(() => {
        setEditData({
            rake_number: rake.rake_number ?? '',
            rake_type: rake.rake_type ?? '',
            dispatch_time: rake.dispatch_time ? new Date(rake.dispatch_time).toISOString().slice(0, 16) : '',
            status: rake.state ?? 'pending',
            rr_expected_date: rake.rr_expected_date
                ? new Date(rake.rr_expected_date).toISOString().slice(0, 10)
                : '',
            placement_time: rake.placement_time
                ? new Date(rake.placement_time).toISOString().slice(0, 10)
                : '',
            loading_date:
                rake.loading_date != null && String(rake.loading_date).length > 0
                    ? String(rake.loading_date).slice(0, 10)
                    : '',
            destination_code: rake.destination_code ?? '',
            remarks: rake.remarks ?? '',
        });
    }, [
        rake.id,
        rake.rake_number,
        rake.rake_type,
        rake.dispatch_time,
        rake.state,
        rake.rr_expected_date,
        rake.placement_time,
        rake.loading_date,
        rake.destination_code,
        rake.remarks,
    ]);

    useEffect(() => {
        if (rake.wagons.length === 0 && (rake.wagon_count ?? 0) > 0) {
            router.visit(`/rakes/${rake.id}/edit`);
        }
    }, [rake.id, rake.wagons.length, rake.wagon_count]);

    useEffect(() => {
        setWagons(rake.wagons);
    }, [rake.wagons]);

    useEffect(() => {
        if (selectedWagon) {
            // no-op: wagon editing is handled in EditWagonsDialog
        }
    }, [selectedWagon]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Rakes', href: '/rakes' },
        { title: rake.rake_number, href: `/rakes/${rake.id}` },
    ];
    const isLow =
        demurrageRemainingMinutes !== null && demurrageRemainingMinutes <= 30;
    const isCritical =
        demurrageRemainingMinutes !== null && demurrageRemainingMinutes <= 0;
    const missingWagonNumberCount = wagons.filter(
        (wagon) => (wagon.wagon_number ?? '').trim().length <= 4
    ).length;
    const missingWagonTypeCount = wagons.filter(
        (wagon) => (wagon.wagon_type ?? '').trim() === ''
    ).length;
    const hasWagonDataGaps = missingWagonNumberCount > 0 || missingWagonTypeCount > 0;

    const rakeForWorkflow = useMemo(
        () => ({ ...rake, wagons }),
        [rake, wagons],
    );

    const latestWeighmentTotalMt = useMemo((): number | null => {
        const latest = (rake.weighments ?? [])[0];
        if (!latest || latest.total_weight_mt === null) {
            return null;
        }
        const n = Number(latest.total_weight_mt);
        return Number.isFinite(n) ? n : null;
    }, [rake.weighments]);

    const predictedPenaltyAmount = useMemo((): number => {
        const predicted = (rake.rakeCharges ?? []).find(
            (c) => c.charge_type === 'PENALTY' && !c.is_actual_charges,
        );
        return (predicted?.appliedPenalties ?? []).reduce((sum, row) => sum + Number(row.amount ?? 0), 0);
    }, [rake.rakeCharges]);

    const actualPenaltyAmount = useMemo((): number => {
        const actual = (rake.rakeCharges ?? []).find(
            (c) => c.charge_type === 'PENALTY' && c.is_actual_charges,
        );
        return (actual?.rrPenaltySnapshots ?? []).reduce((sum, row) => sum + Number(row.amount ?? 0), 0);
    }, [rake.rakeCharges]);

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
                        <WagonOverviewDialog
                            wagons={wagons}
                        />
                        <EditWagonsDialog
                            wagons={wagons}
                            rakeId={rake.id}
                            onWagonSaved={(updatedWagon) =>
                                setWagons((prev) =>
                                    prev.map((wagon) =>
                                        wagon.id === updatedWagon.id
                                            ? { ...wagon, ...updatedWagon }
                                            : wagon
                                    )
                                )
                            }
                        />
                        <Dialog open={editRakeOpen} onOpenChange={setEditRakeOpen}>
                            <DialogTrigger asChild>
                                <Button variant="outline" size="sm">
                                    <Edit className="mr-2 size-4" />
                                    Edit rake
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="max-w-2xl">
                                <DialogHeader>
                                    <DialogTitle>Edit rake</DialogTitle>
                                </DialogHeader>
                                <form
                                    className="grid gap-4 sm:grid-cols-2"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        putEdit(`/rakes/${rake.id}`, {
                                            preserveScroll: true,
                                            onSuccess: () => {
                                                setEditRakeOpen(false);
                                            },
                                        });
                                    }}
                                >
                                    <div>
                                        <Label htmlFor="rake_number">Rake Number</Label>
                                        <Input
                                            id="rake_number"
                                            value={editData.rake_number}
                                            onChange={(e) => setEditData('rake_number', e.target.value)}
                                        />
                                        <InputError message={editErrors.rake_number} />
                                    </div>
                                    <div>
                                        <Label htmlFor="rake_type">Rake Type</Label>
                                        <Input
                                            id="rake_type"
                                            value={editData.rake_type}
                                            onChange={(e) => setEditData('rake_type', e.target.value)}
                                        />
                                        <InputError message={editErrors.rake_type} />
                                    </div>
                                    <div>
                                        <Label htmlFor="dispatch_time">Dispatch Time</Label>
                                        <Input
                                            id="dispatch_time"
                                            type="datetime-local"
                                            value={editData.dispatch_time}
                                            onChange={(e) => setEditData('dispatch_time', e.target.value)}
                                        />
                                        <InputError message={editErrors.dispatch_time} />
                                    </div>
                                    <div>
                                        <Label htmlFor="status">Status</Label>
                                        <select
                                            id="status"
                                            value={editData.status}
                                            onChange={(e) => setEditData('status', e.target.value)}
                                            className="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="pending">Pending</option>
                                            <option value="txr_in_progress">TXR In Progress</option>
                                            <option value="txr_completed">TXR Completed</option>
                                            <option value="loading">Loading</option>
                                            <option value="loading_completed">Loading Completed</option>
                                            <option value="guard_approved">Guard Approved</option>
                                            <option value="guard_rejected">Guard Rejected</option>
                                            <option value="weighment_completed">Weighment Completed</option>
                                            <option value="rr_generated">RR Generated</option>
                                            <option value="closed">Closed</option>
                                        </select>
                                        <InputError message={editErrors.status} />
                                    </div>
                                    <div>
                                        <Label htmlFor="rr_expected_date">RR Expected Date</Label>
                                        <Input
                                            id="rr_expected_date"
                                            type="date"
                                            value={editData.rr_expected_date}
                                            onChange={(e) => setEditData('rr_expected_date', e.target.value)}
                                        />
                                        <InputError message={editErrors.rr_expected_date} />
                                    </div>
                                    <div>
                                        <Label htmlFor="placement_time">Placement Date</Label>
                                        <Input
                                            id="placement_time"
                                            type="date"
                                            value={editData.placement_time}
                                            onChange={(e) => setEditData('placement_time', e.target.value)}
                                        />
                                        <InputError message={editErrors.placement_time} />
                                    </div>
                                    <div>
                                        <Label htmlFor="loading_date">Loading date</Label>
                                        <Input
                                            id="loading_date"
                                            name="loading_date"
                                            type="date"
                                            value={editData.loading_date}
                                            onChange={(e) => setEditData('loading_date', e.target.value)}
                                        />
                                        <InputError message={editErrors.loading_date} />
                                    </div>
                                    <div className="sm:col-span-2">
                                        <Label htmlFor="destination_code">
                                            Destination (power plant)
                                        </Label>
                                        <select
                                            id="destination_code"
                                            name="destination_code"
                                            value={editData.destination_code}
                                            onChange={(e) =>
                                                setEditData('destination_code', e.target.value)
                                            }
                                            className="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">— None —</option>
                                            {powerPlants.map((p) => (
                                                <option key={p.code} value={p.code}>
                                                    {p.name} ({p.code})
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={editErrors.destination_code} />
                                    </div>
                                    <div className="sm:col-span-2">
                                        <Label htmlFor="remarks">Remarks</Label>
                                        <textarea
                                            id="remarks"
                                            value={editData.remarks}
                                            onChange={(e) => setEditData('remarks', e.target.value)}
                                            rows={3}
                                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        />
                                        <InputError message={editErrors.remarks} />
                                    </div>
                                    <div className="sm:col-span-2 flex justify-end gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setEditRakeOpen(false)}
                                            disabled={editProcessing}
                                        >
                                            Cancel
                                        </Button>
                                        <Button type="submit" disabled={editProcessing}>
                                            {editProcessing ? 'Saving...' : 'Save'}
                                        </Button>
                                    </div>
                                </form>
                            </DialogContent>
                        </Dialog>
                        <Link
                            href="/indents"
                            className="text-sm font-medium text-muted-foreground underline underline-offset-4"
                        >
                            ← Back to e-demands
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <Card>
                        <CardHeader className="py-3">
                            <CardDescription className="text-[11px] uppercase tracking-wide">
                                Status
                            </CardDescription>
                            <CardTitle className="text-sm font-semibold capitalize">
                                {rake.state}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="py-3">
                            <CardDescription className="text-[11px] uppercase tracking-wide">
                                Wagons
                            </CardDescription>
                            <div className="flex items-end justify-between gap-2">
                                <CardTitle className="text-sm font-semibold tabular-nums">
                                    {rake.wagon_count ?? 0}
                                </CardTitle>
                                {hasWagonDataGaps ? (
                                    <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                                        Needs update
                                    </span>
                                ) : (
                                    <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                                        OK
                                    </span>
                                )}
                            </div>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="py-3">
                            <CardDescription className="text-[11px] uppercase tracking-wide">
                                Destination
                            </CardDescription>
                            <CardTitle className="text-sm font-semibold">
                                {rake.destination ?? rake.destination_code ?? '—'}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="py-3">
                            <CardDescription className="text-[11px] uppercase tracking-wide">
                                Coal (MT)
                            </CardDescription>
                            <CardTitle className="text-sm font-semibold tabular-nums">
                                {latestWeighmentTotalMt !== null ? latestWeighmentTotalMt.toFixed(2) : '—'}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="py-3">
                            <CardDescription className="text-[11px] uppercase tracking-wide">
                                Predicted (₹)
                            </CardDescription>
                            <CardTitle className="text-sm font-semibold tabular-nums">
                                {predictedPenaltyAmount.toFixed(2)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="py-3">
                            <CardDescription className="text-[11px] uppercase tracking-wide">
                                Actual (₹)
                            </CardDescription>
                            <CardTitle className="text-sm font-semibold tabular-nums">
                                {actualPenaltyAmount.toFixed(2)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                {/* Workflow steps (TXR, Loading, Guard, Weighment, etc.) */}
                <RakeWorkflow
                    rake={rakeForWorkflow}
                    powerPlants={powerPlants}
                    demurrage_rate_per_mt_hour={demurrage_rate_per_mt_hour}
                    onUnfitWagonIdsSynced={(unfitWagonIds) => {
                        const set = new Set(unfitWagonIds);
                        setWagons((prev) =>
                            prev.map((w) => ({
                                ...w,
                                is_unfit: set.has(w.id),
                            })),
                        );
                    }}
                />

                {!rake.txr && wagons.length === 0 && (
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
