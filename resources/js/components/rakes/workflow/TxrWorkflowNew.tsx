import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/input-error';
import { Train, CheckCircle, Clock, AlertTriangle, Plus, Trash2 } from 'lucide-react';
import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface Wagon {
    id: number;
    wagon_number: string;
    wagon_sequence: number;
    wagon_type: string | null;
    is_unfit: boolean;
}

interface TxrRecord {
    id: number;
    inspection_time: string;
    inspection_end_time?: string | null;
    status: string;
    remarks: string | null;
}

interface TxrUnfitWagon {
    id?: number;
    wagon_id: number;
    wagon_number: string;
    wagon_sequence: number;
    wagon_type: string | null;
    reason: string;
    marked_by: string;
    marking_method: string;
    marking_time: string;
}

interface TxrWorkflowNewProps {
    rake: {
        id: number;
        state: string;
        wagons: Wagon[];
        txr: TxrRecord | null;
        txrUnfitWagons?: TxrUnfitWagon[];
    };
    disabled: boolean;
}

export function TxrWorkflowNew({ rake, disabled }: TxrWorkflowNewProps) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    
    // TXR Header state (single record)
    const { data: txrData, setData: setTxrData, post: txrPost, put: txrPut, processing: txrProcessing, reset: txrReset } = useForm({
        inspection_time: rake.txr?.inspection_time ? new Date(rake.txr.inspection_time).toISOString().slice(0, 16) : new Date().toISOString().slice(0, 16),
        inspection_end_time: rake.txr?.inspection_end_time ? new Date(rake.txr.inspection_end_time).toISOString().slice(0, 16) : '',
        status: rake.txr?.status || 'in_progress',
        remarks: rake.txr?.remarks || '',
    });

    // Unfit Wagons state (multiple records)
    const [unfitWagons, setUnfitWagons] = useState<TxrUnfitWagon[]>(
        rake.txrUnfitWagons || []
    );

    const handleStartTxr = () => {
        txrPost(`/rakes/${rake.id}/txr/start`, {
            preserveScroll: true,
            onSuccess: () => {
                window.location.reload();
            },
        });
    };

    const handleSaveTxrHeader = () => {
        if (rake.txr) {
            txrPut(`/rakes/${rake.id}/txr`, {
                preserveScroll: true,
                onSuccess: () => {
                    txrReset();
                },
            });
        } else {
            txrPost(`/rakes/${rake.id}/txr/start`, {
                preserveScroll: true,
                onSuccess: () => {
                    txrReset();
                },
            });
        }
    };

    const handleAddUnfitWagon = () => {
        const newWagon: TxrUnfitWagon = {
            wagon_id: 0,
            wagon_number: '',
            wagon_sequence: 0,
            wagon_type: '',
            reason: '',
            marked_by: '',
            marking_method: '',
            marking_time: new Date().toISOString().slice(0, 16),
        };
        setUnfitWagons([...unfitWagons, newWagon]);
    };

    const handleRemoveUnfitWagon = (index: number) => {
        setUnfitWagons(unfitWagons.filter((_, i) => i !== index));
    };

    const handleWagonSelection = (index: number, wagonId: string) => {
        const wagon = rake.wagons.find(w => w.id.toString() === wagonId);
        if (wagon) {
            const updatedWagons = [...unfitWagons];
            updatedWagons[index] = {
                ...updatedWagons[index],
                wagon_id: wagon.id,
                wagon_number: wagon.wagon_number,
                wagon_sequence: wagon.wagon_sequence,
                wagon_type: wagon.wagon_type || '',
            };
            setUnfitWagons(updatedWagons);
        }
    };

    const handleUnfitWagonChange = (index: number, field: keyof TxrUnfitWagon, value: string) => {
        const updatedWagons = [...unfitWagons];
        updatedWagons[index] = {
            ...updatedWagons[index],
            [field]: value,
        };
        setUnfitWagons(updatedWagons);
    };

    const handleSaveUnfitWagons = () => {
        const formData = new FormData();
        formData.append('unfit_wagons', JSON.stringify(unfitWagons));
        
        txrPost(`/rakes/${rake.id}/txr/unfit-wagons`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setUnfitWagons([]);
            },
        });
    };

    const getStatusIcon = () => {
        if (!rake.txr) return <Clock className="h-4 w-4" />;
        if (rake.txr.status === 'completed') return <CheckCircle className="h-4 w-4 text-green-600" />;
        if (rake.txr.status === 'in_progress') return <Clock className="h-4 w-4 text-blue-600" />;
        return <AlertTriangle className="h-4 w-4 text-orange-600" />;
    };

    const getStatusText = () => {
        if (!rake.txr) return 'Not Started';
        return rake.txr.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    };

    const isCompleted = rake.txr?.status === 'completed';

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Train className="h-5 w-5" />
                        TXR - Train Examination Report
                    </div>
                    <div className="flex items-center gap-2">
                        {getStatusIcon()}
                        <Badge variant={isCompleted ? "default" : "secondary"}>
                            {getStatusText()}
                        </Badge>
                    </div>
                </CardTitle>
                <CardDescription>
                    Train examination and wagon fitness check
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {!rake.txr ? (
                    <div className="text-center py-8">
                        <Button 
                            onClick={handleStartTxr}
                            disabled={disabled || txrProcessing}
                            size="lg"
                        >
                            <Train className="mr-2 h-4 w-4" />
                            Start TXR Inspection
                        </Button>
                    </div>
                ) : (
                    <>
                        {/* PART A: TXR Header Table (Single Row) */}
                        <div className="space-y-4">
                            <h3 className="text-lg font-medium">TXR Header</h3>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="inspection_time">Inspection Start Time</Label>
                                    <Input
                                        id="inspection_time"
                                        name="inspection_time"
                                        type="datetime-local"
                                        value={txrData.inspection_time}
                                        onChange={(e) => setTxrData('inspection_time', e.target.value)}
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
                                        value={txrData.inspection_end_time}
                                        onChange={(e) => setTxrData('inspection_end_time', e.target.value)}
                                    />
                                    <InputError message={errors?.inspection_end_time} />
                                </div>
                                <div>
                                    <Label htmlFor="status">Status</Label>
                                    <Select value={txrData.status} onValueChange={(value) => setTxrData('status', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="in_progress">In Progress</SelectItem>
                                            <SelectItem value="completed">Completed</SelectItem>
                                            <SelectItem value="approved">Approved</SelectItem>
                                            <SelectItem value="rejected">Rejected</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors?.status} />
                                </div>
                                <div>
                                    <Label htmlFor="remarks">Remarks</Label>
                                    <textarea
                                        id="remarks"
                                        name="remarks"
                                        value={txrData.remarks}
                                        onChange={(e) => setTxrData('remarks', e.target.value)}
                                        rows={3}
                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        placeholder="Any inspection remarks..."
                                    />
                                    <InputError message={errors?.remarks} />
                                </div>
                            </div>
                            <div className="flex justify-end">
                                <Button 
                                    onClick={handleSaveTxrHeader}
                                    disabled={txrProcessing}
                                >
                                    Save TXR Header
                                </Button>
                            </div>
                        </div>

                        {/* PART B: Unfit Wagon Details (Multi-row) */}
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-medium">Unfit Wagon Details</h3>
                                <Button
                                    onClick={handleAddUnfitWagon}
                                    variant="outline"
                                    size="sm"
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    Add Row
                                </Button>
                            </div>
                            
                            {unfitWagons.length > 0 && (
                                <div className="space-y-3">
                                    {unfitWagons.map((wagon, index) => (
                                        <div key={index} className="border rounded-lg p-4 space-y-3">
                                            <div className="flex items-center justify-between">
                                                <h4 className="font-medium">Row {index + 1}</h4>
                                                <Button
                                                    onClick={() => handleRemoveUnfitWagon(index)}
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                            
                                            <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                                                <div>
                                                    <Label>Wagon</Label>
                                                    <Select 
                                                        value={wagon.wagon_id.toString()} 
                                                        onValueChange={(value) => handleWagonSelection(index, value)}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select wagon" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {rake.wagons.map((w) => (
                                                                <SelectItem key={w.id} value={w.id.toString()}>
                                                                    {w.wagon_number} (Sequence {w.wagon_sequence})
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                
                                                <div>
                                                    <Label>Wagon Type</Label>
                                                    <Input
                                                        value={wagon.wagon_type || ''}
                                                        readOnly
                                                        className="bg-muted"
                                                    />
                                                </div>
                                                
                                                <div>
                                                    <Label>Reason</Label>
                                                    <Input
                                                        value={wagon.reason}
                                                        onChange={(e) => handleUnfitWagonChange(index, 'reason', e.target.value)}
                                                        placeholder="Reason for unfit"
                                                    />
                                                </div>
                                                
                                                <div>
                                                    <Label>Marked By</Label>
                                                    <Input
                                                        value={wagon.marked_by}
                                                        onChange={(e) => handleUnfitWagonChange(index, 'marked_by', e.target.value)}
                                                        placeholder="Who marked"
                                                    />
                                                </div>
                                                
                                                <div>
                                                    <Label>Marking Method</Label>
                                                    <Select 
                                                        value={wagon.marking_method} 
                                                        onValueChange={(value) => handleUnfitWagonChange(index, 'marking_method', value)}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select method" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="visual">Visual Inspection</SelectItem>
                                                            <SelectItem value="mechanical">Mechanical Test</SelectItem>
                                                            <SelectItem value="ultrasonic">Ultrasonic Test</SelectItem>
                                                            <SelectItem value="other">Other</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                
                                                <div>
                                                    <Label>Marking Time</Label>
                                                    <Input
                                                        type="datetime-local"
                                                        value={wagon.marking_time}
                                                        onChange={(e) => handleUnfitWagonChange(index, 'marking_time', e.target.value)}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                    
                                    <div className="flex justify-end">
                                        <Button 
                                            onClick={handleSaveUnfitWagons}
                                            disabled={txrProcessing}
                                        >
                                            Save Unfit Wagons
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </>
                )}
            </CardContent>
        </Card>
    );
}
