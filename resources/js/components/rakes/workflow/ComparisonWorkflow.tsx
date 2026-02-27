import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { AlertTriangle, CheckCircle, Clock, TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { useState } from 'react';

interface WagonLoading {
    id: number;
    wagon_id: number;
    loaded_quantity_mt: string;
    wagon: {
        id: number;
        wagon_number: string;
        wagon_sequence: number;
    };
}

interface WeighmentRecord {
    wagonWeights?: Array<{
        wagon_id: number;
        gross_weight_mt: string;
        net_weight_mt: string;
        wagon: {
            id: number;
            wagon_number: string;
            wagon_sequence: number;
        };
    }>;
}

interface ComparisonWorkflowProps {
    rake: {
        id: number;
        state: string;
        wagonLoadings?: WagonLoading[];
        weighments?: WeighmentRecord[];
    };
    disabled: boolean;
}

interface ComparisonData {
    wagon_number: string;
    wagon_sequence: number;
    loaded_quantity_mt: number;
    net_weight_mt: number;
    difference_mt: number;
    difference_percent: number;
    status: 'overload' | 'underload' | 'normal';
    action_taken: string;
}

export function ComparisonWorkflow({ rake, disabled }: ComparisonWorkflowProps) {
    const [actionTaken, setActionTaken] = useState<{ [key: number]: string }>({});

    // Calculate comparison data dynamically
    const getComparisonData = (): ComparisonData[] => {
        if (!rake.wagonLoadings?.length || !rake.weighments?.[0]?.wagonWeights?.length) {
            return [];
        }

        const weighmentData = rake.weighments[0].wagonWeights;
        
        return rake.wagonLoadings.map((loading) => {
            const weighment = weighmentData.find(w => w.wagon_id === loading.wagon_id);
            
            if (!weighment) {
                return {
                    wagon_number: loading.wagon.wagon_number,
                    wagon_sequence: loading.wagon.wagon_sequence,
                    loaded_quantity_mt: parseFloat(loading.loaded_quantity_mt),
                    net_weight_mt: 0,
                    difference_mt: 0,
                    difference_percent: 0,
                    status: 'normal' as const,
                    action_taken: actionTaken[loading.wagon_id] || '',
                };
            }

            const loadedQty = parseFloat(loading.loaded_quantity_mt);
            const netWeight = parseFloat(weighment.net_weight_mt);
            const difference = loadedQty - netWeight;
            const differencePercent = netWeight > 0 ? (difference / netWeight) * 100 : 0;

            let status: 'overload' | 'underload' | 'normal' = 'normal';
            if (Math.abs(differencePercent) > 5) {
                status = difference > 0 ? 'overload' : 'underload';
            }

            return {
                wagon_number: loading.wagon.wagon_number,
                wagon_sequence: loading.wagon.wagon_sequence,
                loaded_quantity_mt: loadedQty,
                net_weight_mt: netWeight,
                difference_mt: difference,
                difference_percent: differencePercent,
                status,
                action_taken: actionTaken[loading.wagon_id] || '',
            };
        });
    };

    const comparisonData = getComparisonData();
    const hasData = comparisonData.length > 0;
    const hasIssues = comparisonData.some(d => d.status !== 'normal');

    const getStatusIcon = () => {
        if (!hasData) return <Clock className="h-4 w-4" />;
        if (hasIssues) return <AlertTriangle className="h-4 w-4 text-orange-600" />;
        return <CheckCircle className="h-4 w-4 text-green-600" />;
    };

    const getStatusText = () => {
        if (!hasData) return 'Not Available';
        if (hasIssues) return 'Issues Found';
        return 'All Normal';
    };

    const getStatusVariant = () => {
        if (!hasData) return "secondary";
        if (hasIssues) return "destructive";
        return "default";
    };

    const getDifferenceIcon = (status: string) => {
        switch (status) {
            case 'overload':
                return <TrendingUp className="h-4 w-4 text-red-600" />;
            case 'underload':
                return <TrendingDown className="h-4 w-4 text-blue-600" />;
            default:
                return <Minus className="h-4 w-4 text-green-600" />;
        }
    };

    const getDifferenceColor = (status: string) => {
        switch (status) {
            case 'overload':
                return 'text-red-600';
            case 'underload':
                return 'text-blue-600';
            default:
                return 'text-green-600';
        }
    };

    const handleActionChange = (wagonId: number, action: string) => {
        setActionTaken((prev: { [key: number]: string }) => ({
            ...prev,
            [wagonId]: action,
        }));
    };

    const totalLoaded = comparisonData.reduce((sum, d) => sum + d.loaded_quantity_mt, 0);
    const totalWeighed = comparisonData.reduce((sum, d) => sum + d.net_weight_mt, 0);
    const totalDifference = totalLoaded - totalWeighed;

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <AlertTriangle className="h-5 w-5" />
                        Loader vs Weighment Comparison
                    </div>
                    <div className="flex items-center gap-2">
                        {getStatusIcon()}
                        <Badge variant={getStatusVariant()}>
                            {getStatusText()}
                        </Badge>
                    </div>
                </CardTitle>
                <CardDescription>
                    Compare loader recorded quantities with actual weighment results
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {hasData ? (
                    <>
                        <div className="grid gap-4 md:grid-cols-3">
                            <div>
                                <Label>Total Loaded</Label>
                                <p className="text-lg font-bold">{totalLoaded.toFixed(2)} MT</p>
                            </div>
                            <div>
                                <Label>Total Weighed</Label>
                                <p className="text-lg font-bold">{totalWeighed.toFixed(2)} MT</p>
                            </div>
                            <div>
                                <Label>Difference</Label>
                                <p className={`text-lg font-bold ${
                                    totalDifference > 0 ? 'text-red-600' : 
                                    totalDifference < 0 ? 'text-blue-600' : 'text-green-600'
                                }`}>
                                    {totalDifference > 0 ? '+' : ''}{totalDifference.toFixed(2)} MT
                                </p>
                            </div>
                        </div>

                        <div>
                            <Label className="text-base font-medium">Wagon-wise Comparison</Label>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Wagon</TableHead>
                                        <TableHead>Loaded (MT)</TableHead>
                                        <TableHead>Weighed (MT)</TableHead>
                                        <TableHead>Difference</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Action Taken</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {comparisonData.map((data) => (
                                        <TableRow key={data.wagon_sequence}>
                                            <TableCell>
                                                {data.wagon_number} (Pos {data.wagon_sequence})
                                            </TableCell>
                                            <TableCell>{data.loaded_quantity_mt.toFixed(2)}</TableCell>
                                            <TableCell>{data.net_weight_mt.toFixed(2)}</TableCell>
                                            <TableCell className={getDifferenceColor(data.status)}>
                                                <div className="flex items-center gap-1">
                                                    {getDifferenceIcon(data.status)}
                                                    {data.difference_mt > 0 ? '+' : ''}{data.difference_mt.toFixed(2)} MT
                                                    ({data.difference_percent.toFixed(1)}%)
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={
                                                    data.status === 'normal' ? 'default' : 'destructive'
                                                }>
                                                    {data.status.toUpperCase()}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Input
                                                    value={data.action_taken}
                                                    onChange={(e) => handleActionChange(
                                                        comparisonData.find(d => d.wagon_sequence === data.wagon_sequence)?.wagon_sequence || 0, 
                                                        e.target.value
                                                    )}
                                                    placeholder="Enter action..."
                                                    className="text-sm"
                                                    disabled={disabled}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="p-4 bg-gray-50 rounded-lg">
                            <h4 className="font-medium mb-2">Summary</h4>
                            <div className="text-sm space-y-1">
                                <p>• Normal wagons: {comparisonData.filter(d => d.status === 'normal').length}</p>
                                <p>• Overloaded wagons: {comparisonData.filter(d => d.status === 'overload').length}</p>
                                <p>• Underloaded wagons: {comparisonData.filter(d => d.status === 'underload').length}</p>
                                <p>• Total variance: {Math.abs(totalDifference).toFixed(2)} MT</p>
                            </div>
                        </div>
                    </>
                ) : (
                    <div className="text-center py-8 text-sm text-muted-foreground">
                        {disabled ? 
                            'Complete weighment to enable comparison' : 
                            'No data available for comparison'
                        }
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
