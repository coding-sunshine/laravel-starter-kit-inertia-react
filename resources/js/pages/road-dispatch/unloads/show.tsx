import InputError from '@/components/input-error';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, Form, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import UnloadTimeline from '../stepper/track-timeline';
import WeighmentHistory from '../components/WeighmentHistory';

interface Step {
    id: number;
    step_number: number;
    status: string;
    started_at: string | null;
    completed_at: string | null;
}

interface Weighment {
    id: number;
    weighment_type: 'GROSS' | 'TARE';
    weighment_status: string;
    weighment_time: string;
}

interface Unload {
    id: number;
    arrival_time: string;
    unload_start_time: string | null;
    unload_end_time: string | null;
    state: string;
    steps: Step[];
    weighments: Weighment[];
    vehicleArrival?: {
        id: number;
        gross_weight?: number | null;
        tare_weight?: number | null;
        net_weight?: number | null;
    };
    siding?: { id: number; name: string; code: string };
    vehicle?: { id: number; vehicle_number: string; owner_name: string | null };
}

interface Props {
    unload: Unload;
    lastGrossWeight?: number | null;
}

export default function RoadDispatchUnloadsShow({ unload, lastGrossWeight }: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const [grossOpen, setGrossOpen] = useState(false);
    const [tareOpen, setTareOpen] = useState(false);

    useEffect(() => {
        const echo = (window as { Echo?: { private: (ch: string) => { listen: (ev: string, cb: () => void) => void } } }).Echo;
        if (echo) {
            const channel = echo.private(`unload.${unload.id}`);
            channel.listen('.step.updated', () => router.reload());
            return () => channel.stopListening('.step.updated');
        }
    }, [unload.id]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Road Dispatch', href: '/road-dispatch/unloads' },
        { title: 'Unload', href: `/road-dispatch/unloads/${unload.id}` },
    ];

    const step2 = unload.steps.find((s) => s.step_number === 2);
    const step3 = unload.steps.find((s) => s.step_number === 3);
    const step4 = unload.steps.find((s) => s.step_number === 4);
    const step5 = unload.steps.find((s) => s.step_number === 5);

    const isStep2Failed = step2?.status === 'FAILED';
    const isStep3Failed = step3?.status === 'FAILED';
    const isStep4Failed = step4?.status === 'FAILED';
    const isStep5Failed = step5?.status === 'FAILED';

    const isStep2Active = isStep2Failed || step2?.status === 'IN_PROGRESS';
    const isStep3Active = isStep3Failed || step3?.status === 'IN_PROGRESS';
    const isStep4Active = isStep4Failed || step4?.status === 'IN_PROGRESS';
    const isStep5Active = isStep5Failed || step5?.status === 'IN_PROGRESS';

    const isCancelled =
        unload.state === 'CANCELLED' ||
        unload.steps.some(
            (s) => s.status === 'FAILED' || s.status === 'CANCELLED',
        );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Unload #${unload.id}`} />
            <div className="space-y-6">
                <Heading
                    title={`Unload #${unload.id}`}
                    description={
                        unload.siding && unload.vehicle
                            ? `${unload.siding.code} – ${unload.vehicle.vehicle_number}`
                            : 'Vehicle unload workflow'
                    }
                />
                <div className="flex gap-2">
                    <Link href="/road-dispatch/unloads">
                        <Button variant="outline">Back to unloads</Button>
                    </Link>
                </div>
                <UnloadTimeline 
                    unload={unload} 
                    actions={{
                        grossWeighment: isStep2Active && (
                            <Dialog open={grossOpen} onOpenChange={setGrossOpen}>
                                <DialogTrigger asChild>
                                    <Button size="sm" className="shadow-sm" variant={isStep2Failed ? "destructive" : "default"}>
                                        {isStep2Failed ? "Retry Gross Weighment" : "Record Gross Weighment"}
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>Record Gross Weighment</DialogTitle>
                                        <DialogDescription>
                                            Enter gross weight and pass/fail status.
                                        </DialogDescription>
                                    </DialogHeader>
                                    {unload.vehicleArrival?.gross_weight && (
                                        <div className="bg-blue-50 border border-blue-200 rounded-md p-3">
                                            <p className="text-sm text-blue-800">
                                                <strong>Arrival Weight:</strong> {unload.vehicleArrival.gross_weight} MT
                                            </p>
                                            <p className="text-xs text-blue-600 mt-1">
                                                Gross weight must match the arrival weight (±0.01 MT tolerance)
                                            </p>
                                        </div>
                                    )}
                                    <Form
                                        method="post"
                                        action={`/road-dispatch/unloads/${unload.id}/gross-weighment`}
                                        className="space-y-4"
                                        onSubmit={() => setGrossOpen(false)}
                                    >
                                        <div className="grid gap-2">
                                            <Label htmlFor="gross_weight_mt">Gross weight (MT) *</Label>
                                            <Input
                                                id="gross_weight_mt"
                                                name="gross_weight_mt"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                required
                                                placeholder={unload.vehicleArrival?.gross_weight?.toString()}
                                            />
                                            <InputError message={errors?.gross_weight_mt} />
                                        </div>
                                        <DialogFooter>
                                            <Button type="button" variant="outline" onClick={() => setGrossOpen(false)}>
                                                Cancel
                                            </Button>
                                            <Button type="submit">Save</Button>
                                        </DialogFooter>
                                    </Form>
                                </DialogContent>
                            </Dialog>
                        ),
                        startUnload: isStep3Active && (
                            <Button
                                size="sm"
                                className="shadow-sm"
                                onClick={() =>
                                    router.post(
                                        `/road-dispatch/unloads/${unload.id}/start-unload`,
                                    )
                                }
                            >
                                Start Unloading
                            </Button>
                        ),
                        tareWeighment: isStep4Active && (
                            <Dialog open={tareOpen} onOpenChange={setTareOpen}>
                                <DialogTrigger asChild>
                                    <Button size="sm" className="shadow-sm" variant={isStep4Failed ? "destructive" : "default"}>
                                        {isStep4Failed ? "Retry Tare Weighment" : "Record Tare Weighment"}
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>Record Tare Weighment</DialogTitle>
                                        <DialogDescription>
                                            Enter gross (from weighbridge), tare weight, and pass/fail.
                                        </DialogDescription>
                                    </DialogHeader>
                                    {unload.vehicleArrival?.gross_weight && (
                                        <div className="bg-blue-50 border border-blue-200 rounded-md p-3">
                                            <p className="text-sm text-blue-800">
                                                <strong>Arrival Weights:</strong> Gross: {unload.vehicleArrival.gross_weight} MT
                                                {unload.vehicleArrival.tare_weight && ` / Tare: ${unload.vehicleArrival.tare_weight} MT`}
                                            </p>
                                            <p className="text-xs text-blue-600 mt-1">
                                                Weights must match arrival weights (±0.01 MT tolerance)
                                            </p>
                                        </div>
                                    )}
                                    <Form
                                        method="post"
                                        action={`/road-dispatch/unloads/${unload.id}/tare-weighment`}
                                        className="space-y-4"
                                        onSubmit={() => setTareOpen(false)}
                                    >
                                        <div className="grid gap-2">
                                            <Label htmlFor="gross_weight_mt">Gross weight (MT) *</Label>
                                            <Input
                                                id="gross_weight_mt"
                                                name="gross_weight_mt"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                required
                                                defaultValue={lastGrossWeight ?? ''}
                                                placeholder={unload.vehicleArrival?.gross_weight?.toString()}
                                            />
                                            <InputError message={errors?.gross_weight_mt} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="tare_weight_mt">Tare weight (MT) *</Label>
                                            <Input
                                                id="tare_weight_mt"
                                                name="tare_weight_mt"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                required
                                                placeholder={unload.vehicleArrival?.tare_weight?.toString()}
                                            />
                                            <InputError message={errors?.tare_weight_mt} />
                                        </div>
                                        <DialogFooter>
                                            <Button type="button" variant="outline" onClick={() => setTareOpen(false)}>
                                                Cancel
                                            </Button>
                                            <Button type="submit">Save</Button>
                                        </DialogFooter>
                                    </Form>
                                </DialogContent>
                            </Dialog>
                        ),
                        completeUnload: isStep5Active && (
                            <Button
                                size="sm"
                                className="shadow-sm"
                                onClick={() =>
                                    router.post(
                                        `/road-dispatch/unloads/${unload.id}/complete`,
                                    )
                                }
                            >
                                Complete Unload
                            </Button>
                        ),
                    }}
                    errors={errors}
                />
                
                {/* Weighment History */}
                <WeighmentHistory weighments={unload.weighments} />
            </div>
        </AppLayout>
    );
}
