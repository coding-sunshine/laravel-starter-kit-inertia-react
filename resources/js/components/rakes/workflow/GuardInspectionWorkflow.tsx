import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import InputError from '@/components/input-error';
import { Shield, CheckCircle, Clock, XCircle, CalendarClock } from 'lucide-react';
import { useForm, usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { cn } from '@/lib/utils';

interface GuardInspectionRecord {
    id: number;
    inspection_time?: string;
    inspection_start_time?: string;
    movement_permission_time: string;
    is_approved: boolean;
    remarks: string | null;
}

interface GuardInspectionWorkflowProps {
    rake: {
        id: number;
        state: string;
        guardInspections?: GuardInspectionRecord[];
    };
    disabled: boolean;
}

export function GuardInspectionWorkflow({ rake, disabled }: GuardInspectionWorkflowProps) {
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

    const inspection = rake.guardInspections?.[0];
    const hasInspection = !!inspection;
    const isApproved = inspection?.is_approved;

    const getStatusIcon = () => {
        if (!hasInspection) return <Clock className="h-4 w-4" />;
        if (isApproved) return <CheckCircle className="h-4 w-4 text-green-600" />;
        return <XCircle className="h-4 w-4 text-red-600" />;
    };

    const getStatusText = () => {
        if (!hasInspection) return 'Not Inspected';
        return isApproved ? 'Approved' : 'Rejected';
    };

    const getStatusVariant = () => {
        if (!hasInspection) return "secondary";
        return isApproved ? "default" : "destructive";
    };

    const inspectionDisplayTime = inspection?.inspection_start_time ?? inspection?.inspection_time ?? '';

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
                        <Clock className="h-4 w-4 text-muted-foreground shrink-0" />
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

    const setInspectionTime = (date: string, time: string) => {
        const d = date || (time ? new Date().toISOString().slice(0, 10) : '');
        setData('inspection_time', d ? `${d}T${time || '00:00'}` : '');
    };
    const setMovementPermissionTime = (date: string, time: string) => {
        const d = date || (time ? new Date().toISOString().slice(0, 10) : '');
        setData('movement_permission_time', d ? `${d}T${time || '00:00'}` : '');
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Shield className="h-5 w-5" />
                        Guard Inspection
                    </div>
                    <div className="flex items-center gap-2">
                        {getStatusIcon()}
                        <Badge variant={getStatusVariant()}>
                            {getStatusText()}
                        </Badge>
                    </div>
                </CardTitle>
                <CardDescription>
                    Guard inspection and movement permission
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {!hasInspection ? (
                    <div>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="inspection_time">Inspection Time</Label>
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
                                <InputError message={errors?.inspection_time} />
                            </div>
                            <div>
                                <Label htmlFor="movement_permission_time">Movement Permission Time</Label>
                                <DateTimePopover
                                    value={data.movement_permission_time}
                                    onChange={setMovementPermissionTime}
                                    disabled={disabled}
                                    placeholder="Select date & time"
                                />
                                <input
                                    type="hidden"
                                    name="movement_permission_time"
                                    value={data.movement_permission_time}
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
                                    disabled={disabled}
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
                                    disabled={disabled}
                                />
                                <InputError message={errors?.remarks} />
                            </div>
                            <div className="flex justify-end space-x-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => reset()}
                                    disabled={disabled}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={disabled || processing}>
                                    Record Inspection
                                </Button>
                            </div>
                        </form>
                    </div>
                ) : (
                    <div className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>Inspection Time</Label>
                                <p className="text-sm">
                                    {inspectionDisplayTime
                                        ? new Date(inspectionDisplayTime).toLocaleString()
                                        : '-'}
                                </p>
                            </div>
                            <div>
                                <Label>Movement Permission Time</Label>
                                <p className="text-sm">
                                    {new Date(inspection.movement_permission_time).toLocaleString()}
                                </p>
                            </div>
                        </div>
                        
                        <div className="flex items-center gap-2">
                            <Label>Decision:</Label>
                            <Badge variant={isApproved ? "default" : "destructive"}>
                                {isApproved ? 'Approved for Movement' : 'Rejected'}
                            </Badge>
                        </div>

                        {inspection.remarks && (
                            <div>
                                <Label>Remarks</Label>
                                <p className="text-sm text-muted-foreground">{inspection.remarks}</p>
                            </div>
                        )}

                        <div className={`flex items-center gap-2 text-sm ${
                            isApproved ? 'text-green-600' : 'text-red-600'
                        }`}>
                            {isApproved ? (
                                <>
                                    <CheckCircle className="h-4 w-4" />
                                    Guard approved for movement
                                </>
                            ) : (
                                <>
                                    <XCircle className="h-4 w-4" />
                                    Guard rejected - workflow blocked
                                </>
                            )}
                        </div>
                    </div>
                )}

                {/* Table Format Display */}
                {rake.guardInspections && rake.guardInspections.length > 0 && (
                    <div className="space-y-4">
                        <Label className="text-base font-medium">Guard Inspection Records</Label>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Inspection Time</TableHead>
                                    <TableHead>Movement Permission Time</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Remarks</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rake.guardInspections.map((insp) => (
                                    <TableRow key={insp.id}>
                                        <TableCell>
                                            {(insp.inspection_start_time ?? insp.inspection_time)
                                                ? new Date(insp.inspection_start_time ?? insp.inspection_time!).toLocaleString()
                                                : '-'}
                                        </TableCell>
                                        <TableCell>
                                            {new Date(insp.movement_permission_time).toLocaleString()}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={insp.is_approved ? "default" : "destructive"}>
                                                {insp.is_approved ? 'Approved' : 'Rejected'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {insp.remarks || '-'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {disabled && !hasInspection && (
                    <div className="text-center py-4 text-sm text-muted-foreground">
                        Complete wagon loading to enable guard inspection
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
