import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Train, Clock, Save } from 'lucide-react';
import { useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';

interface TxrData {
    id?: number;
    txr_start_time?: string;
    txr_end_time?: string;
    duration_minutes?: number;
    remarks?: string | null;
}

interface RakeData {
    id: number;
    rake_number: string;
    state: string;
    placement_time?: string | null;
    siding?: {
        name: string;
        code: string;
    } | null;
    txr?: TxrData | null;
}

interface TxrTableProps {
    rake: RakeData;
    disabled: boolean;
}

export function TxrTable({ rake, disabled }: TxrTableProps) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const [duration, setDuration] = useState<number>(0);
    
    const { data, setData, post, put, processing, reset } = useForm({
        txr_start_time: rake.txr?.txr_start_time ? new Date(rake.txr.txr_start_time).toISOString().slice(0, 16) : '',
        txr_end_time: rake.txr?.txr_end_time ? new Date(rake.txr.txr_end_time).toISOString().slice(0, 16) : '',
        remarks: rake.txr?.remarks || '',
    });

    // Real-time duration calculation
    useEffect(() => {
        if (data.txr_start_time && data.txr_end_time) {
            const start = new Date(data.txr_start_time);
            const end = new Date(data.txr_end_time);
            
            if (!isNaN(start.getTime()) && !isNaN(end.getTime()) && end >= start) {
                const diffMinutes = Math.floor((end.getTime() - start.getTime()) / (1000 * 60));
                setDuration(diffMinutes);
            } else {
                setDuration(0);
            }
        } else {
            setDuration(0);
        }
    }, [data.txr_start_time, data.txr_end_time]);

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        
        if (rake.txr) {
            put(`/rakes/${rake.id}/txr`, {
                preserveScroll: true,
                onSuccess: () => {
                    // Success handling
                },
            });
        } else {
            post(`/rakes/${rake.id}/txr`, {
                preserveScroll: true,
                onSuccess: () => {
                    // Success handling
                },
            });
        }
    };

    const formatDateTime = (dateTimeString: string | null | undefined) => {
        if (!dateTimeString) return '-';
        return new Date(dateTimeString).toLocaleString();
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Train className="h-5 w-5" />
                    TXR Header Information
                </CardTitle>
                <CardDescription>
                    Train Examination Report timing and details
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Rake No</TableHead>
                                <TableHead>Siding</TableHead>
                                <TableHead>Rake Placement Time</TableHead>
                                <TableHead>TXR Start Time</TableHead>
                                <TableHead>TXR End Time</TableHead>
                                <TableHead>TXR Duration (Min)</TableHead>
                                <TableHead>No of Unfit Wagons</TableHead>
                                <TableHead>Remarks</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow>
                                <TableCell className="font-medium">
                                    {rake.rake_number}
                                </TableCell>
                                <TableCell>
                                    {rake.siding ? `${rake.siding.name} (${rake.siding.code})` : '-'}
                                </TableCell>
                                <TableCell>
                                    {formatDateTime(rake.placement_time)}
                                </TableCell>
                                <TableCell>
                                    <Input
                                        type="datetime-local"
                                        value={data.txr_start_time}
                                        onChange={(e) => setData('txr_start_time', e.target.value)}
                                        disabled={disabled}
                                        required
                                    />
                                    <InputError message={errors?.txr_start_time} />
                                </TableCell>
                                <TableCell>
                                    <Input
                                        type="datetime-local"
                                        value={data.txr_end_time}
                                        onChange={(e) => setData('txr_end_time', e.target.value)}
                                        disabled={disabled}
                                        required
                                    />
                                    <InputError message={errors?.txr_end_time} />
                                </TableCell>
                                <TableCell>
                                    <div className="flex items-center gap-2">
                                        <Clock className="h-4 w-4" />
                                        <span className="font-medium">{duration}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">0</Badge>
                                </TableCell>
                                <TableCell>
                                    <textarea
                                        value={data.remarks}
                                        onChange={(e) => setData('remarks', e.target.value)}
                                        disabled={disabled}
                                        rows={2}
                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none"
                                        placeholder="Add remarks..."
                                    />
                                    <InputError message={errors?.remarks} />
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div className="flex justify-end space-x-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => reset()}
                            disabled={disabled}
                        >
                            Reset
                        </Button>
                        <Button 
                            type="submit" 
                            disabled={disabled || processing}
                            className="flex items-center gap-2"
                        >
                            <Save className="h-4 w-4" />
                            {rake.txr ? 'Update TXR' : 'Save TXR'}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
