import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import InputError from '@/components/input-error';
import { Scale, CheckCircle, Clock, AlertTriangle, Upload, FileText } from 'lucide-react';
import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface Wagon {
    id: number;
    wagon_number: string;
    wagon_sequence: number;
    pcc_weight_mt: string | null;
}

interface WeighmentRecord {
    id: number;
    weighment_time: string;
    total_weight_mt: string;
    status: string | null;
    train_speed_kmph: number;
    attempt_no: number;
    wagonWeights?: Array<{
        wagon_id: number;
        gross_weight_mt: string;
        net_weight_mt: string;
        wagon: Wagon;
    }>;
}

interface WeighmentWorkflowProps {
    rake: {
        id: number;
        state: string;
        wagons: Wagon[];
        weighments?: WeighmentRecord[];
    };
    disabled: boolean;
}

export function WeighmentWorkflow({ rake, disabled }: WeighmentWorkflowProps) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const [pdfFile, setPdfFile] = useState<File | null>(null);
    
    const { data, setData, post, processing, reset } = useForm({
        weighment_pdf: null as File | null,
        train_speed_kmph: '',
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setPdfFile(file);
            setData('weighment_pdf', file);
        }
    };

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const formData = new FormData();
        if (pdfFile) {
            formData.append('weighment_pdf', pdfFile);
        }
        formData.append('train_speed_kmph', data.train_speed_kmph);
        
        post(`/rakes/${rake.id}/weighments`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setPdfFile(null);
            },
        });
    };

    const latestWeighment = rake.weighments?.[0];
    const hasWeighment = !!latestWeighment;
    const isCompleted = hasWeighment && latestWeighment.status === 'success';

    const getStatusIcon = () => {
        if (!hasWeighment) return <Clock className="h-4 w-4" />;
        if (isCompleted) return <CheckCircle className="h-4 w-4 text-green-600" />;
        return <AlertTriangle className="h-4 w-4 text-orange-600" />;
    };

    const getStatusText = () => {
        if (!hasWeighment) return 'Not Weighed';
        if (isCompleted) return 'Completed';
        return latestWeighment.status || 'Processing';
    };

    const getStatusVariant = () => {
        if (!hasWeighment) return "secondary";
        if (isCompleted) return "default";
        return "destructive";
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Scale className="h-5 w-5" />
                        Rake Weighment
                    </div>
                    <div className="flex items-center gap-2">
                        {getStatusIcon()}
                        <Badge variant={getStatusVariant()}>
                            {getStatusText()}
                        </Badge>
                    </div>
                </CardTitle>
                <CardDescription>
                    Upload weighment PDF and record weights
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {!hasWeighment ? (
                    <div>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="weighment_pdf">Weighment PDF Document</Label>
                                <div className="mt-2 flex items-center gap-4">
                                    <Input
                                        id="weighment_pdf"
                                        type="file"
                                        accept=".pdf"
                                        onChange={handleFileChange}
                                        disabled={disabled}
                                        className="file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    />
                                    {pdfFile && (
                                        <div className="flex items-center gap-2 text-sm text-green-600">
                                            <FileText className="h-4 w-4" />
                                            {pdfFile.name}
                                        </div>
                                    )}
                                </div>
                                <p className="text-xs text-muted-foreground mt-1">
                                    Upload the official weighment certificate (PDF format)
                                </p>
                                <InputError message={errors?.weighment_pdf} />
                            </div>
                            
                            <div>
                                <Label htmlFor="train_speed_kmph">Train Speed (km/h)</Label>
                                <Input
                                    id="train_speed_kmph"
                                    name="train_speed_kmph"
                                    type="number"
                                    step="0.1"
                                    min="5"
                                    max="7"
                                    value={data.train_speed_kmph}
                                    onChange={(e) => setData('train_speed_kmph', e.target.value)}
                                    placeholder="5-7 km/h required"
                                    required
                                    disabled={disabled}
                                />
                                <p className="text-xs text-muted-foreground mt-1">
                                    Speed must be between 5-7 km/h for accurate weighment
                                </p>
                                <InputError message={errors?.train_speed_kmph} />
                            </div>

                            <div className="flex justify-end space-x-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        reset();
                                        setPdfFile(null);
                                    }}
                                    disabled={disabled}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={disabled || processing || !pdfFile}>
                                    <Upload className="mr-2 h-4 w-4" />
                                    Upload & Process
                                </Button>
                            </div>
                        </form>
                    </div>
                ) : (
                    <div className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-3">
                            <div>
                                <Label>Weighment Time</Label>
                                <p className="text-sm">
                                    {new Date(latestWeighment.weighment_time).toLocaleString()}
                                </p>
                            </div>
                            <div>
                                <Label>Total Weight</Label>
                                <p className="text-lg font-bold">{latestWeighment.total_weight_mt} MT</p>
                            </div>
                            <div>
                                <Label>Train Speed</Label>
                                <p className="text-sm">{latestWeighment.train_speed_kmph} km/h</p>
                            </div>
                        </div>

                        {latestWeighment.wagonWeights && latestWeighment.wagonWeights.length > 0 && (
                            <div>
                                <Label className="text-base font-medium">Wagon Weights</Label>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Wagon</TableHead>
                                            <TableHead>Gross Weight (MT)</TableHead>
                                            <TableHead>Net Weight (MT)</TableHead>
                                            <TableHead>PCC Weight (MT)</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {latestWeighment.wagonWeights.map((wagonWeight) => (
                                            <TableRow key={wagonWeight.wagon_id}>
                                                <TableCell>
                                                    {wagonWeight.wagon.wagon_number} (Pos {wagonWeight.wagon.wagon_sequence})
                                                </TableCell>
                                                <TableCell>{wagonWeight.gross_weight_mt}</TableCell>
                                                <TableCell>{wagonWeight.net_weight_mt}</TableCell>
                                                <TableCell>{wagonWeight.wagon.pcc_weight_mt || 'N/A'}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}

                        <div className={`flex items-center gap-2 text-sm ${
                            isCompleted ? 'text-green-600' : 'text-orange-600'
                        }`}>
                            {isCompleted ? (
                                <>
                                    <CheckCircle className="h-4 w-4" />
                                    Weighment completed successfully
                                </>
                            ) : (
                                <>
                                    <AlertTriangle className="h-4 w-4" />
                                    Weighment failed - attempt #{latestWeighment.attempt_no}
                                </>
                            )}
                        </div>

                        {!isCompleted && (
                            <div className="text-center">
                                <Button 
                                    onClick={() => window.location.reload()}
                                    variant="outline"
                                >
                                    Try Again
                                </Button>
                            </div>
                        )}
                    </div>
                )}

                {disabled && !hasWeighment && (
                    <div className="text-center py-4 text-sm text-muted-foreground">
                        Complete guard inspection to enable weighment
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
