import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import InputError from '@/components/input-error';
import { Package, CheckCircle, Clock, AlertTriangle, Plus, Trash2 } from 'lucide-react';
import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface Wagon {
    id: number;
    wagon_number: string;
    wagon_sequence: number;
    wagon_type: string | null;
    pcc_weight_mt: string | null;
    is_unfit: boolean;
}

interface Loader {
    id: number;
    loader_name: string;
    code: string;
}

interface WagonLoadingRow {
    id?: number;
    wagon_id: number;
    wagon_number: string;
    wagon_sequence: number;
    wagon_type: string | null;
    pcc_capacity: string | null;
    loader_id: number;
    loader_name: string;
    loader_code: string;
    loaded_quantity_mt: string;
    loading_time: string;
    remarks: string;
}

interface WagonLoadingWorkflowNewProps {
    rake: {
        id: number;
        state: string;
        wagons: Wagon[];
        wagonLoadings?: WagonLoadingRow[];
        siding?: {
            loaders?: Loader[];
        } | null;
    };
    disabled: boolean;
}

export function WagonLoadingWorkflowNew({ rake, disabled }: WagonLoadingWorkflowNewProps) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    
    // Multi-row state for wagon loading
    const [loadingRows, setLoadingRows] = useState<WagonLoadingRow[]>(
        rake.wagonLoadings || []
    );

    const { data, setData, post, processing, reset } = useForm({
        loading_rows: loadingRows,
    });

    const handleAddRow = () => {
        const newRow: WagonLoadingRow = {
            wagon_id: 0,
            wagon_number: '',
            wagon_sequence: 0,
            wagon_type: '',
            pcc_capacity: '',
            loader_id: 0,
            loader_name: '',
            loader_code: '',
            loaded_quantity_mt: '',
            loading_time: new Date().toISOString().slice(0, 16),
            remarks: '',
        };
        setLoadingRows([...loadingRows, newRow]);
    };

    const handleRemoveRow = (index: number) => {
        setLoadingRows(loadingRows.filter((_, i) => i !== index));
    };

    const handleWagonSelection = (index: number, wagonId: string) => {
        const wagon = rake.wagons.find(w => w.id.toString() === wagonId);
        if (wagon) {
            const updatedRows = [...loadingRows];
            updatedRows[index] = {
                ...updatedRows[index],
                wagon_id: wagon.id,
                wagon_number: wagon.wagon_number,
                wagon_sequence: wagon.wagon_sequence,
                wagon_type: wagon.wagon_type || '',
                pcc_capacity: wagon.pcc_weight_mt || '',
            };
            setLoadingRows(updatedRows);
        }
    };

    const handleLoaderSelection = (index: number, loaderId: string) => {
        const loader = rake.siding?.loaders?.find(l => l.id.toString() === loaderId);
        if (loader) {
            const updatedRows = [...loadingRows];
            updatedRows[index] = {
                ...updatedRows[index],
                loader_id: loader.id,
                loader_name: loader.loader_name,
                loader_code: loader.code,
            };
            setLoadingRows(updatedRows);
        }
    };

    const handleRowChange = (index: number, field: keyof WagonLoadingRow, value: string) => {
        const updatedRows = [...loadingRows];
        updatedRows[index] = {
            ...updatedRows[index],
            [field]: value,
        };
        setLoadingRows(updatedRows);
    };

    const handleSave = () => {
        const formData = new FormData();
        formData.append('loading_rows', JSON.stringify(loadingRows));
        
        post(`/rakes/${rake.id}/load/wagon/batch`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setLoadingRows([]);
                reset();
            },
        });
    };

    // Get available wagons (excluding already loaded and unfit wagons)
    const loadedWagonIds = loadingRows.map(row => row.wagon_id).filter(id => id > 0);
    const availableWagons = rake.wagons.filter(wagon => 
        !loadedWagonIds.includes(wagon.id) && !wagon.is_unfit
    );

    const isCompleted = availableWagons.length === 0 && loadingRows.length > 0;

    const getStatusIcon = () => {
        if (isCompleted) return <CheckCircle className="h-4 w-4 text-green-600" />;
        if (loadingRows.length > 0) return <Package className="h-4 w-4 text-blue-600" />;
        return <Clock className="h-4 w-4" />;
    };

    const getStatusText = () => {
        if (isCompleted) return 'Completed';
        if (loadingRows.length > 0) return 'In Progress';
        return 'Not Started';
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Package className="h-5 w-5" />
                        Wagon Loading
                    </div>
                    <div className="flex items-center gap-2">
                        {getStatusIcon()}
                        <Badge variant={isCompleted ? "default" : "secondary"}>
                            {getStatusText()}
                        </Badge>
                    </div>
                </CardTitle>
                <CardDescription>
                    Load multiple wagons with specified quantities
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {loadingRows.length > 0 && (
                    <div className="space-y-4">
                        <Label className="text-base font-medium">Loading Entries</Label>
                        <div className="space-y-3">
                            {loadingRows.map((row, index) => (
                                <div key={index} className="border rounded-lg p-4 space-y-3">
                                    <div className="flex items-center justify-between">
                                        <h4 className="font-medium">Row {index + 1}</h4>
                                        <Button
                                            onClick={() => handleRemoveRow(index)}
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
                                                value={row.wagon_id.toString()} 
                                                onValueChange={(value) => handleWagonSelection(index, value)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select wagon" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {rake.wagons.map((wagon) => (
                                                        <SelectItem key={wagon.id} value={wagon.id.toString()}>
                                                            {wagon.wagon_number} (Sequence {wagon.wagon_sequence})
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        
                                        <div>
                                            <Label>Loader</Label>
                                            <Select 
                                                value={row.loader_id.toString()} 
                                                onValueChange={(value) => handleLoaderSelection(index, value)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select loader" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {rake.siding?.loaders?.map((loader) => (
                                                        <SelectItem key={loader.id} value={loader.id.toString()}>
                                                            {loader.loader_name} ({loader.code})
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        
                                        <div>
                                            <Label>Wagon Type</Label>
                                            <Input
                                                value={row.wagon_type || ''}
                                                readOnly
                                                className="bg-muted"
                                            />
                                        </div>
                                        
                                        <div>
                                            <Label>PCC Capacity</Label>
                                            <Input
                                                value={row.pcc_capacity ? `${row.pcc_capacity} MT` : ''}
                                                readOnly
                                                className="bg-muted"
                                            />
                                        </div>
                                        
                                        <div>
                                            <Label>Loaded Quantity (MT)</Label>
                                            <Input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={row.loaded_quantity_mt}
                                                onChange={(e) => handleRowChange(index, 'loaded_quantity_mt', e.target.value)}
                                                placeholder="Quantity loaded"
                                            />
                                        </div>
                                        
                                        <div>
                                            <Label>Loading Time</Label>
                                            <Input
                                                type="datetime-local"
                                                value={row.loading_time}
                                                onChange={(e) => handleRowChange(index, 'loading_time', e.target.value)}
                                            />
                                        </div>
                                        
                                        <div className="md:col-span-2 lg:col-span-3">
                                            <Label>Remarks</Label>
                                            <textarea
                                                value={row.remarks}
                                                onChange={(e) => handleRowChange(index, 'remarks', e.target.value)}
                                                rows={2}
                                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                placeholder="Any loading remarks..."
                                            />
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {!isCompleted && (
                    <div className="flex items-center justify-between">
                        <Button
                            onClick={handleAddRow}
                            variant="outline"
                            disabled={disabled}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Add Row
                        </Button>
                        
                        {loadingRows.length > 0 && (
                            <div className="flex space-x-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setLoadingRows([]);
                                        reset();
                                    }}
                                    disabled={disabled}
                                >
                                    Clear All
                                </Button>
                                <Button 
                                    onClick={handleSave}
                                    disabled={disabled || processing}
                                >
                                    Save Loading Entries
                                </Button>
                            </div>
                        )}
                    </div>
                )}

                {isCompleted && (
                    <div className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>Total Wagons Loaded</Label>
                                <p className="text-2xl font-bold">{loadingRows.length}</p>
                            </div>
                            <div>
                                <Label>Total Quantity</Label>
                                <p className="text-2xl font-bold">
                                    {loadingRows.reduce((sum, row) => 
                                        sum + parseFloat(row.loaded_quantity_mt || '0'), 0
                                    ).toFixed(2)} MT
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2 text-sm text-green-600">
                            <CheckCircle className="h-4 w-4" />
                            All wagons loaded successfully
                        </div>
                    </div>
                )}

                {disabled && !isCompleted && (
                    <div className="text-center py-4 text-sm text-muted-foreground">
                        Complete previous steps to enable wagon loading
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
