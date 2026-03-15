import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import InputError from '@/components/input-error';
import { Scale, CheckCircle, Clock, AlertTriangle, Plus, Calculator } from 'lucide-react';
import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface PenaltyRecord {
    id: number;
    penalty_type: string;
    penalty_amount: string;
    penalty_status: string;
    penalty_date: string;
    description: string | null;
    calculation_breakdown?: {
        formula?: string;
        demurrage_hours?: number;
        weight_mt?: number;
        rate_per_mt_hour?: number;
        free_hours?: number | null;
        dwell_hours?: number | null;
    } | null;
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
    demurrage_rate_per_mt_hour: number;
    disabled: boolean;
}

export function PenaltiesWorkflow({ rake, demurrage_rate_per_mt_hour, disabled }: PenaltiesWorkflowProps) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const [showAddForm, setShowAddForm] = useState(false);
    
    const { data, setData, post, processing, reset } = useForm({
        penalty_type: 'manual',
        penalty_amount: '',
        description: '',
    });

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(`/penalties`, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setShowAddForm(false);
            },
        });
    };

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

    const calculateAutoDemurrage = () => {
        // This would be calculated based on actual data
        // For now, just show the formula
        return {
            formula: `(elapsed_hours - free_hours) × weight × rate`,
            rate_per_mt_hour: demurrage_rate_per_mt_hour,
        };
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
                    Calculate and manage penalties for this rake
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {/* Auto-calculation section */}
                <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div className="flex items-center gap-2 mb-2">
                        <Calculator className="h-4 w-4 text-blue-600" />
                        <Label className="text-blue-800 font-medium">Auto-Calculation</Label>
                    </div>
                    <div className="text-sm text-blue-700 space-y-1">
                        <p>Demurrage Formula: (elapsed_hours - free_hours) × weight × ₹{demurrage_rate_per_mt_hour}/MT/h</p>
                        <p className="text-xs">Based on loading time vs free time allowance</p>
                    </div>
                    <Button 
                        size="sm" 
                        variant="outline"
                        className="mt-2"
                        disabled={disabled}
                    >
                        Calculate Demurrage
                    </Button>
                </div>

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
                                    <TableHead>Excess (MT)</TableHead>
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
                                            {ap.quantity != null ? `${ap.quantity}` : '-'}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            ₹{Number(ap.amount ?? 0).toFixed(2)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <div className="mt-2 text-xs text-muted-foreground">
                            Total weighment-based penalties:&nbsp;
                            <span className="font-semibold">
                                ₹{totalAppliedAmount.toFixed(2)}
                            </span>
                        </div>
                    </div>
                )}

                {/* Manual / demurrage penalty form */}
                {showAddForm && (
                    <div className="p-4 border rounded-lg">
                        <Label className="text-base font-medium mb-4 block">Add Manual Penalty</Label>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="penalty_type">Penalty Type</Label>
                                <select
                                    id="penalty_type"
                                    name="penalty_type"
                                    value={data.penalty_type}
                                    onChange={(e) => setData('penalty_type', e.target.value)}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    required
                                    disabled={disabled}
                                >
                                    <option value="manual">Manual</option>
                                    <option value="overload">Overload</option>
                                    <option value="delay">Delay</option>
                                    <option value="other">Other</option>
                                </select>
                                <InputError message={errors?.penalty_type} />
                            </div>
                            
                            <div>
                                <Label htmlFor="penalty_amount">Penalty Amount (₹)</Label>
                                <Input
                                    id="penalty_amount"
                                    name="penalty_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.penalty_amount}
                                    onChange={(e) => setData('penalty_amount', e.target.value)}
                                    placeholder="Enter penalty amount"
                                    required
                                    disabled={disabled}
                                />
                                <InputError message={errors?.penalty_amount} />
                            </div>

                            <div>
                                <Label htmlFor="description">Description</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={3}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Penalty description and reason..."
                                    disabled={disabled}
                                />
                                <InputError message={errors?.description} />
                            </div>

                            <div className="flex justify-end space-x-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        reset();
                                        setShowAddForm(false);
                                    }}
                                    disabled={disabled}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={disabled || processing}>
                                    Add Penalty
                                </Button>
                            </div>
                        </form>
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

                        {penalties.some(p => p.calculation_breakdown) && (
                            <div className="mt-4 p-3 bg-gray-50 rounded text-sm">
                                <Label className="font-medium">Calculation Details:</Label>
                                {penalties.filter(p => p.calculation_breakdown).map((penalty, idx) => (
                                    <div key={penalty.id} className="mt-1">
                                        {penalty.calculation_breakdown?.formula}
                                        {penalty.calculation_breakdown?.demurrage_hours != null && (
                                            <span className="ml-2 text-muted-foreground">
                                                = {penalty.calculation_breakdown.demurrage_hours}h × {penalty.calculation_breakdown.weight_mt}MT × ₹{penalty.calculation_breakdown.rate_per_mt_hour}/MT/h
                                            </span>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                ) : (
                    <div className="text-center py-8 text-sm text-muted-foreground">
                        No penalties recorded yet
                    </div>
                )}

                {/* Add penalty button */}
                {!showAddForm && (
                    <div className="flex justify-center">
                        <Button
                            variant="outline"
                            onClick={() => setShowAddForm(true)}
                            disabled={disabled}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Add Manual Penalty
                        </Button>
                    </div>
                )}

                {disabled && !hasPenalties && (
                    <div className="text-center py-4 text-sm text-muted-foreground">
                        Complete previous steps to enable penalty management
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
