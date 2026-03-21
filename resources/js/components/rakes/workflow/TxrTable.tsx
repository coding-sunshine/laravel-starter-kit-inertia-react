import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import InputError from '@/components/input-error';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Train, Clock, Save, CalendarClock } from 'lucide-react';
import { useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { format } from 'date-fns';
import { cn } from '@/lib/utils';

interface TxrData {
    id?: number;
    inspection_time?: string;
    inspection_end_time?: string | null;
    duration_minutes?: number;
    remarks?: string | null;
    status?: string;
    wagonUnfitLogs?: unknown[];
    wagon_unfit_logs?: unknown[];
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
    onTxrHeaderSaved?: (txr: Record<string, unknown>) => void;
}

export function TxrTable({ rake, disabled, onTxrHeaderSaved }: TxrTableProps) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const [duration, setDuration] = useState<number>(0);
    
    const { data, setData, put, processing, reset } = useForm({
        inspection_time: rake.txr?.inspection_time ? new Date(rake.txr.inspection_time).toISOString().slice(0, 16) : '',
        inspection_end_time: rake.txr?.inspection_end_time ? new Date(rake.txr.inspection_end_time).toISOString().slice(0, 16) : '',
        status: rake.txr?.status ?? 'in_progress',
        remarks: rake.txr?.remarks || '',
    });

    useEffect(() => {
        setData({
            inspection_time: rake.txr?.inspection_time
                ? new Date(rake.txr.inspection_time).toISOString().slice(0, 16)
                : '',
            inspection_end_time: rake.txr?.inspection_end_time
                ? new Date(rake.txr.inspection_end_time).toISOString().slice(0, 16)
                : '',
            status: rake.txr?.status ?? 'in_progress',
            remarks: rake.txr?.remarks || '',
        });
    }, [
        rake.txr?.id,
        rake.txr?.inspection_time,
        rake.txr?.inspection_end_time,
        rake.txr?.status,
        rake.txr?.remarks,
        setData,
    ]);

    const setInspectionTime = (date: string, time: string) => {
        const d = date || (time ? new Date().toISOString().slice(0, 10) : '');
        setData('inspection_time', d ? `${d}T${time || '00:00'}` : '');
    };
    const setInspectionEndTime = (date: string, time: string) => {
        const d = date || (time ? new Date().toISOString().slice(0, 10) : '');
        setData('inspection_end_time', d ? `${d}T${time || '00:00'}` : '');
    };

    function DateTimePopover({
        value,
        onChange,
        disabled,
        placeholder,
    }: {
        value: string;
        onChange: (date: string, time: string) => void;
        disabled: boolean;
        placeholder: string;
    }) {
        const datePart = value ? value.slice(0, 10) : '';
        const timePart = value ? value.slice(11, 16) : '';
        const displayDate = datePart ? new Date(value) : null;

        return (
            <Popover>
                <PopoverTrigger asChild>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={disabled}
                        className={cn(
                            'w-full justify-start text-left font-normal min-h-9',
                            !displayDate && 'text-muted-foreground'
                        )}
                    >
                        <CalendarClock className="mr-2 h-4 w-4" />
                        {displayDate ? (
                            <>
                                {format(displayDate, 'dd MMM yyyy')} · {timePart || '00:00'}
                            </>
                        ) : (
                            placeholder
                        )}
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="start">
                    <div className="p-3 border-b">
                        <Calendar
                            mode="single"
                            selected={datePart ? new Date(datePart) : undefined}
                            onSelect={(d) => onChange(d ? d.toISOString().slice(0, 10) : '', timePart)}
                            initialFocus
                            disabled={disabled}
                        />
                    </div>
                    <div className="p-3 flex items-center gap-2">
                        <Clock className="h-4 w-4 text-muted-foreground" />
                        <Input
                            type="time"
                            value={timePart}
                            onChange={(e) =>
                                onChange(datePart || new Date().toISOString().slice(0, 10), e.target.value)
                            }
                            disabled={disabled}
                            className="w-full"
                        />
                    </div>
                </PopoverContent>
            </Popover>
        );
    }

    // Real-time duration calculation
    useEffect(() => {
        if (data.inspection_time && data.inspection_end_time) {
            const start = new Date(data.inspection_time);
            const end = new Date(data.inspection_end_time);

            if (!isNaN(start.getTime()) && !isNaN(end.getTime()) && end >= start) {
                const diffMinutes = Math.floor((end.getTime() - start.getTime()) / (1000 * 60));
                setDuration(diffMinutes);
            } else {
                setDuration(0);
            }
        } else {
            setDuration(0);
        }
    }, [data.inspection_time, data.inspection_end_time]);

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        put(`/rakes/${rake.id}/txr`, {
            preserveScroll: true,
            onSuccess: (page) => {
                const props = page.props as { rake?: { txr?: Record<string, unknown> | null } };
                const nextTxr = props.rake?.txr;
                if (nextTxr) {
                    onTxrHeaderSaved?.(nextTxr);
                }
            },
        });
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
                                <TableHead>Status</TableHead>
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
                                    <div>
                                        <DateTimePopover
                                            value={data.inspection_time}
                                            onChange={setInspectionTime}
                                            disabled={disabled}
                                            placeholder="Select date & time"
                                        />
                                        <input
                                            type="hidden"
                                            name="inspection_time"
                                            value={data.inspection_time}
                                            required
                                        />
                                    </div>
                                    <InputError message={errors?.inspection_time} />
                                </TableCell>
                                <TableCell>
                                    <div>
                                        <DateTimePopover
                                            value={data.inspection_end_time}
                                            onChange={setInspectionEndTime}
                                            disabled={disabled}
                                            placeholder="Select date & time"
                                        />
                                        <input type="hidden" name="inspection_end_time" value={data.inspection_end_time} />
                                    </div>
                                    <InputError message={errors?.inspection_end_time} />
                                </TableCell>
                                <TableCell>
                                    <Select
                                        value={data.status}
                                        onValueChange={(value) => setData('status', value)}
                                        disabled={disabled}
                                    >
                                        <SelectTrigger className="h-9 w-[140px]">
                                            <SelectValue placeholder="Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pending">Pending</SelectItem>
                                            <SelectItem value="in_progress">In progress</SelectItem>
                                            <SelectItem value="completed">Completed</SelectItem>
                                            <SelectItem value="approved">Approved</SelectItem>
                                            <SelectItem value="rejected">Rejected</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors?.status} />
                                </TableCell>
                                <TableCell>
                                    <div className="flex items-center gap-2">
                                        <Clock className="h-4 w-4" />
                                        <span className="font-medium">{duration}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">
                                        {rake.txr?.wagonUnfitLogs?.length ??
                                            (rake.txr?.wagon_unfit_logs?.length ?? 0)}
                                    </Badge>
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
