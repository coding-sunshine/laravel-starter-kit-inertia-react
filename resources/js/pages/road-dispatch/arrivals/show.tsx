import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { StatusPill } from '@/components/status-pill';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Calendar, Clock, Truck, User, Weight } from 'lucide-react';

interface Arrival {
    id: number;
    status: string;
    arrived_at: string;
    unloading_started_at: string | null;
    unloading_completed_at: string | null;
    gross_weight: number | null;
    tare_weight: number | null;
    net_weight: number | null;
    unloaded_quantity: number | null;
    notes: string | null;
    shift: string | null;
    siding: {
        id: number;
        name: string;
        code: string;
    };
    vehicle: {
        id: number;
        vehicle_number: string;
        owner_name: string;
    };
    vehicle_unload: {
        id: number;
        vehicle_arrival_id: number;
        unload_start_time: string | null;
        unload_end_time: string | null;
        state: string;
    } | null;
    creator: {
        id: number;
        name: string;
    } | null;
    updater: {
        id: number;
        name: string;
    } | null;
}

interface Props {
    arrival: Arrival;
}

export default function RoadDispatchArrivalsShow({ arrival }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Road Dispatch', href: '/road-dispatch/arrivals' },
        { title: 'Vehicle Arrivals', href: '/road-dispatch/arrivals' },
        { title: `Arrival #${arrival.id}`, href: `/road-dispatch/arrivals/${arrival.id}` },
    ];

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString();
    };

    const formatTime = (dateString: string | null) => {
        if (!dateString) return '—';
        return new Date(dateString).toLocaleTimeString();
    };

    const getActionButton = () => {
        // Debug logging
        console.log('Arrival data:', arrival);
        console.log('VehicleUnload:', arrival.vehicle_unload);
        console.log('Unload state:', arrival.vehicle_unload?.state);
        
        // If no unload exists, show "Start Unload"
        if (!arrival.vehicle_unload) {
            console.log('No unload exists - showing Start Unload');
            return (
                <Link href={`/road-dispatch/arrivals/${arrival.id}/unload`}>
                    <Button>
                        <Truck className="mr-2 h-4 w-4" />
                        Start Unload
                    </Button>
                </Link>
            );
        }

        // If unload exists, show appropriate action based on status
        const unloadState = arrival.vehicle_unload.state;
        console.log('Unload state found:', unloadState);
        
        switch (unloadState) {
            case 'PENDING':
                console.log('Showing Start Unload for PENDING');
                return (
                    <Link href={`/road-dispatch/arrivals/${arrival.id}/unload`}>
                        <Button>
                            <Truck className="mr-2 h-4 w-4" />
                            Start Unload
                        </Button>
                    </Link>
                );
                
            case 'IN_PROGRESS':
                console.log('Showing Continue Unload for IN_PROGRESS');
                return (
                    <Link href={`/road-dispatch/unloads/${arrival.vehicle_unload.id}`}>
                        <Button>
                            <Truck className="mr-2 h-4 w-4" />
                            Continue Unload
                        </Button>
                    </Link>
                );
                
            case 'COMPLETED':
                console.log('Showing View Unload Details for COMPLETED');
                return (
                    <Link href={`/road-dispatch/unloads/${arrival.vehicle_unload.id}`}>
                        <Button variant="outline">
                            <Truck className="mr-2 h-4 w-4" />
                            View Unload Details
                        </Button>
                    </Link>
                );
                
            case 'CANCELLED':
                console.log('Showing View Cancelled Unload for CANCELLED');
                return (
                    <Link href={`/road-dispatch/unloads/${arrival.vehicle_unload.id}`}>
                        <Button variant="outline">
                            <Truck className="mr-2 h-4 w-4" />
                            View Cancelled Unload
                        </Button>
                    </Link>
                );
                
            default:
                console.log('Showing default View Unload for unknown state:', unloadState);
                return (
                    <Link href={`/road-dispatch/unloads/${arrival.vehicle_unload.id}`}>
                        <Button variant="outline">
                            <Truck className="mr-2 h-4 w-4" />
                            View Unload
                        </Button>
                    </Link>
                );
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Vehicle Arrival #${arrival.id}`} />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Vehicle Arrival Details</h1>
                        <p className="text-muted-foreground">
                            Arrival #{arrival.id} from {formatDate(arrival.arrived_at)}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/road-dispatch/arrivals">
                            <Button variant="outline">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back to Arrivals
                            </Button>
                        </Link>
                        {getActionButton()}
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Truck className="h-5 w-5" />
                                Vehicle Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Vehicle Number</p>
                                    <p className="font-semibold">{arrival.vehicle.vehicle_number}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Owner</p>
                                    <p>{arrival.vehicle.owner_name}</p>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Siding</p>
                                    <p>{arrival.siding.code} ({arrival.siding.name})</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Shift</p>
                                    <p>{arrival.shift || '—'}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Status Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="h-5 w-5" />
                                Status & Timeline
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Current Status</p>
                                <div className="mt-1">
                                    <StatusPill status={arrival.status} />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <span className="text-sm text-muted-foreground">Arrived:</span>
                                    <span className="text-sm">{formatDate(arrival.arrived_at)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-muted-foreground">Unloading Started:</span>
                                    <span className="text-sm">{formatTime(arrival.unloading_started_at)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-muted-foreground">Unloading Completed:</span>
                                    <span className="text-sm">{formatTime(arrival.unloading_completed_at)}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Weight Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Weight className="h-5 w-5" />
                                Weight Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-3 gap-4">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Gross</p>
                                    <p className="font-semibold">{arrival.gross_weight || '—'} MT</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Tare</p>
                                    <p className="font-semibold">{arrival.tare_weight || '—'} MT</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Net</p>
                                    <p className="font-semibold">{arrival.net_weight || '—'} MT</p>
                                </div>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Unloaded Quantity</p>
                                <p className="font-semibold">{arrival.unloaded_quantity || '—'} MT</p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Additional Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <User className="h-5 w-5" />
                                Additional Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Notes</p>
                                <p className="mt-1">{arrival.notes || 'No notes provided'}</p>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Created By</p>
                                    <p className="text-sm">{arrival.creator?.name || '—'}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Last Updated By</p>
                                    <p className="text-sm">{arrival.updater?.name || '—'}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Unload Status */}
                {arrival.vehicle_unload && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Unload Status</CardTitle>
                            <CardDescription>
                                Current status of the vehicle unloading process
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Unload State</p>
                                    <div className="mt-1">
                                        <StatusPill status={arrival.vehicle_unload.state} />
                                    </div>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Started At</p>
                                    <p className="text-sm">{formatTime(arrival.vehicle_unload.unload_start_time)}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Completed At</p>
                                    <p className="text-sm">{formatTime(arrival.vehicle_unload.unload_end_time)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
