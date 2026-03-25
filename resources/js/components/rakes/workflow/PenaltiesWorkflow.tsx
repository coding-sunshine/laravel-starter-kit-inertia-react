import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Scale, Clock } from 'lucide-react';

interface PenaltyRecord {
    id: number;
    penalty_type: string;
    penalty_amount: string;
    penalty_status: string;
    penalty_date: string;
    description: string | null;
}

interface PenaltiesWorkflowProps {
    rake: {
        id: number;
        state: string;
        penalties?: PenaltyRecord[];
        appliedPenalties?: Array<{
            id: number;
            amount: string | number;
            quantity?: string | number | null;
            wagon_id?: number | null;
            wagon_number?: string | null;
            penalty_type?: { id: number; code: string; name: string; calculation_type: string };
            wagon?: { id: number; wagon_number: string; overload_weight_mt?: string | number | null };
        }>;
    };
    disabled: boolean;
}

function formatExcessValue(quantity: string | number | null | undefined, calculationType?: string): string {
    if (quantity == null) {
        return '-';
    }
    const value = Number(quantity);
    if (calculationType === 'per_hour') {
        return `${value} ${value === 1 ? 'hour' : 'hours'}`;
    }
    return `${value} MT`;
}

export function PenaltiesWorkflow({ rake, disabled }: PenaltiesWorkflowProps) {
    const penalties = rake.penalties || [];
    const appliedPenalties = rake.appliedPenalties || [];
    const hasPenalties = penalties.length > 0;
    const totalPenaltyAmount = penalties.reduce((sum, p) => sum + parseFloat(p.penalty_amount), 0);

    const hasAppliedPenalties = appliedPenalties.length > 0;
    const totalAppliedAmount = appliedPenalties.reduce(
        (sum, ap) => sum + Number(ap.amount ?? 0),
        0,
    );

    const getStatusIcon = () => {
        if (!hasPenalties && !hasAppliedPenalties) return <Clock className="h-4 w-4" />;
        return <Scale className="h-4 w-4 text-orange-600" />;
    };

    const getStatusText = () => {
        if (!hasPenalties && !hasAppliedPenalties) return 'No Penalties';
        const count = penalties.length + appliedPenalties.length;
        return `${count} Penalties`;
    };

    const getStatusVariant = () => {
        if (!hasPenalties) return "secondary";
        return "destructive";
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Scale className="h-5 w-5" />
                        Penalties
                    </div>
                    <div className="flex items-center gap-2">
                        {getStatusIcon()}
                        <Badge variant={getStatusVariant()}>
                            {getStatusText()}
                        </Badge>
                    </div>
                </CardTitle>
                <CardDescription>
                    Penalties applied for this rake
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {/* Weighment-based penalties */}
                {hasAppliedPenalties && (
                    <div className="p-4 border rounded-lg">
                        <Label className="text-base font-medium mb-2 block">
                            Weighment-based penalties
                        </Label>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Wagon</TableHead>
                                    <TableHead>Excess</TableHead>
                                    <TableHead>Amount</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {appliedPenalties.map((ap) => (
                                    <TableRow key={ap.id}>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {ap.penalty_type?.code ?? '-'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {ap.wagon_number ??
                                                ap.wagon?.wagon_number ??
                                                (ap.wagon_id != null ? `Wagon #${ap.wagon_id}` : 'Rake')}
                                        </TableCell>
                                        <TableCell>
                                            {formatExcessValue(ap.quantity, ap.penalty_type?.calculation_type)}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            ₹{Number(ap.amount ?? 0).toFixed(2)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <div className="mt-2 text-xs text-muted-foreground">
                            Total penalties:&nbsp;
                            <span className="font-semibold">
                                ₹{totalAppliedAmount.toFixed(2)}
                            </span>
                        </div>
                    </div>
                )}

                {/* Penalties list */}
                {hasPenalties ? (
                    <div>
                        <div className="flex justify-between items-center mb-4">
                            <Label className="text-base font-medium">Penalty Records</Label>
                            <div className="text-lg font-bold text-red-600">
                                Total: ₹{totalPenaltyAmount.toFixed(2)}
                            </div>
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Description</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {penalties.map((penalty) => (
                                    <TableRow key={penalty.id}>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {penalty.penalty_type.replace('_', ' ').toUpperCase()}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="font-medium">₹{penalty.penalty_amount}</TableCell>
                                        <TableCell>
                                            <Badge variant={
                                                penalty.penalty_status === 'paid' ? 'default' : 'secondary'
                                            }>
                                                {penalty.penalty_status.toUpperCase()}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{penalty.penalty_date}</TableCell>
                                        <TableCell className="max-w-xs truncate">
                                            {penalty.description || 'N/A'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                ) : !hasAppliedPenalties ? (
                    <div className="text-center py-8 text-sm text-muted-foreground">
                        No penalties recorded yet
                    </div>
                ) : null}

                {disabled && !hasPenalties && !hasAppliedPenalties && (
                    <div className="text-center py-4 text-sm text-muted-foreground">
                        Complete previous steps to enable penalty management
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
