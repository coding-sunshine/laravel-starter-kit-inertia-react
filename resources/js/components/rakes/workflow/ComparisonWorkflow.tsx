import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { AlertTriangle, CheckCircle, Clock, TrendingUp, TrendingDown, Minus } from 'lucide-react';

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
        gross_weight_mt: number;
        net_weight_mt: number;
        wagon: {
            id: number;
            wagon_number: string;
            wagon_sequence: number;
            pcc_weight_mt?: number | null;
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
    loader_qty_mt: number | null;
    inmotion_qty_mt: number;
    difference_mt: number | null;
    flag: 'OVER' | 'UNDER' | 'OK' | 'N/A';
    action_taken: string;
}

export function ComparisonWorkflow({ rake, disabled }: ComparisonWorkflowProps) {
    // Calculate comparison data dynamically
    const getComparisonData = (): ComparisonData[] => {
        const weighmentWagons = rake.weighments?.[0]?.wagonWeights ?? [];
        if (weighmentWagons.length === 0) {
            return [];
        }

        const loaderLoadings = rake.wagonLoadings ?? [];

        const loaderById = new Map<number, WagonLoading>();
        loaderLoadings.forEach((l) => loaderById.set(l.wagon_id, l));

        const loaderByNumber = new Map<string, WagonLoading>();
        loaderLoadings.forEach((l) => {
            const wagonNumber = l.wagon?.wagon_number;
            if (!wagonNumber) return;
            loaderByNumber.set(wagonNumber, l);
        });

        // Base set: all weighment wagons.
        return weighmentWagons.map((weighment) => {
            const inmotionQty = Number(weighment.net_weight_mt);

            const loaderByIdHit = weighment.wagon_id ? loaderById.get(weighment.wagon_id) : undefined;
            const loaderByNumberHit =
                weighment.wagon?.wagon_number && loaderByNumber.has(weighment.wagon.wagon_number)
                    ? loaderByNumber.get(weighment.wagon.wagon_number)
                    : undefined;

            const loading = loaderByIdHit ?? loaderByNumberHit;

            if (!loading) {
                return {
                    wagon_number: weighment.wagon.wagon_number,
                    wagon_sequence: weighment.wagon.wagon_sequence,
                    loader_qty_mt: null,
                    inmotion_qty_mt: inmotionQty,
                    difference_mt: null,
                    flag: 'N/A',
                    action_taken: '-',
                };
            }

            const loaderQty = Number(loading.loaded_quantity_mt);
            const difference = loaderQty - inmotionQty;
            const flag: 'OVER' | 'UNDER' | 'OK' =
                difference > 0 ? 'OVER' : difference < 0 ? 'UNDER' : 'OK';

            return {
                wagon_number: weighment.wagon.wagon_number,
                wagon_sequence: weighment.wagon.wagon_sequence,
                loader_qty_mt: loaderQty,
                inmotion_qty_mt: inmotionQty,
                difference_mt: difference,
                flag,
                action_taken: '-',
            };
        });
    };

    const comparisonData = getComparisonData();
    const hasData = comparisonData.length > 0;
    const hasIssues = comparisonData.some((d) => d.flag === 'OVER' || d.flag === 'UNDER');
    const incompleteRowsCount = comparisonData.filter((d) => d.difference_mt === null).length;

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

    const getFlagIcon = (flag: ComparisonData['flag']) => {
        switch (flag) {
            case 'OVER':
                return <TrendingUp className="h-4 w-4 text-red-600" />;
            case 'UNDER':
                return <TrendingDown className="h-4 w-4 text-blue-600" />;
            case 'OK':
                return <Minus className="h-4 w-4 text-green-600" />;
            default:
                return <Clock className="h-4 w-4 text-muted-foreground" />;
        }
    };

    const getFlagColor = (flag: ComparisonData['flag']) => {
        switch (flag) {
            case 'OVER':
                return 'text-red-600';
            case 'UNDER':
                return 'text-blue-600';
            case 'OK':
                return 'text-green-600';
            default:
                return 'text-muted-foreground';
        }
    };

    const totalLoaded = comparisonData.reduce((sum, d) => sum + (d.loader_qty_mt ?? 0), 0);
    const totalWeighed = comparisonData.reduce((sum, d) => sum + d.inmotion_qty_mt, 0);
    const totalDifference = comparisonData.reduce((sum, d) => sum + (d.difference_mt ?? 0), 0);

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
                                <p
                                    className={`text-lg font-bold ${
                                        incompleteRowsCount > 0
                                            ? 'text-muted-foreground'
                                            : totalDifference > 0
                                                ? 'text-red-600'
                                                : totalDifference < 0
                                                    ? 'text-blue-600'
                                                    : 'text-green-600'
                                    }`}
                                >
                                    {incompleteRowsCount > 0
                                        ? 'N/A'
                                        : `${totalDifference > 0 ? '+' : ''}${totalDifference.toFixed(2)} MT`}
                                </p>
                            </div>
                        </div>

                        <div>
                            <Label className="text-base font-medium">Wagon-wise Comparison</Label>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Wagon</TableHead>
                                        <TableHead>Loader Qty (MT)</TableHead>
                                        <TableHead>Inmotion Qty (MT)</TableHead>
                                        <TableHead>Difference (MT)</TableHead>
                                        <TableHead>Overload/Underload Flag</TableHead>
                                        <TableHead>Action Taken</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {comparisonData.map((data) => (
                                        <TableRow key={data.wagon_sequence}>
                                            <TableCell>
                                                {data.wagon_number} (Pos {data.wagon_sequence})
                                            </TableCell>
                                            <TableCell>
                                                {data.loader_qty_mt === null ? 'N/A' : data.loader_qty_mt.toFixed(2)}
                                            </TableCell>
                                            <TableCell>{data.inmotion_qty_mt.toFixed(2)}</TableCell>
                                            <TableCell className={getFlagColor(data.flag)}>
                                                <div className="flex items-center gap-1">
                                                    {getFlagIcon(data.flag)}
                                                    {data.difference_mt === null
                                                        ? 'N/A'
                                                        : `${data.difference_mt > 0 ? '+' : ''}${data.difference_mt.toFixed(2)} MT`}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {data.flag === 'N/A' ? (
                                                    <Badge variant="secondary">N/A</Badge>
                                                ) : (
                                                    <Badge variant={data.flag === 'OK' ? 'default' : 'destructive'}>
                                                        {data.flag}
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell>{data.action_taken}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="p-4 bg-gray-50 rounded-lg">
                            <h4 className="font-medium mb-2">Summary</h4>
                            <div className="text-sm space-y-1">
                                <p>• OK wagons: {comparisonData.filter((d) => d.flag === 'OK').length}</p>
                                <p>• Overloaded wagons: {comparisonData.filter((d) => d.flag === 'OVER').length}</p>
                                <p>• Underloaded wagons: {comparisonData.filter((d) => d.flag === 'UNDER').length}</p>
                                <p>• Total variance: {incompleteRowsCount > 0 ? 'N/A' : `${Math.abs(totalDifference).toFixed(2)} MT`}</p>
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
