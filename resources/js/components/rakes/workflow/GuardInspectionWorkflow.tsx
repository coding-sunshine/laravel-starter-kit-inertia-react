import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import InputError from '@/components/input-error';
import { Shield, CheckCircle, Clock, AlertTriangle, XCircle } from 'lucide-react';
import { useForm, usePage } from '@inertiajs/react';

interface GuardInspectionRecord {
    id: number;
    inspection_time: string;
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
                                <Input
                                    id="inspection_time"
                                    name="inspection_time"
                                    type="datetime-local"
                                    value={data.inspection_time}
                                    onChange={(e) => setData('inspection_time', e.target.value)}
                                    required
                                    disabled={disabled}
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
                                    disabled={disabled}
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
                                    {new Date(inspection.inspection_time).toLocaleString()}
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
                                {rake.guardInspections.map((inspection) => (
                                    <TableRow key={inspection.id}>
                                        <TableCell>
                                            {new Date(inspection.inspection_time).toLocaleString()}
                                        </TableCell>
                                        <TableCell>
                                            {new Date(inspection.movement_permission_time).toLocaleString()}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={inspection.is_approved ? "default" : "destructive"}>
                                                {inspection.is_approved ? 'Approved' : 'Rejected'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {inspection.remarks || '-'}
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
