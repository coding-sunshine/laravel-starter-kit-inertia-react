import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Scale, CheckCircle, Clock, AlertTriangle, Upload, FileText, Trash2, Eye } from 'lucide-react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

interface WeighmentRecord {
    id: number;
    weighment_time: string;
    total_weight_mt: string | number | null;
    status: string | null;
    train_speed_kmph: number | string | null;
    attempt_no: number;
}

interface WeighmentWorkflowProps {
    rake: {
        id: number;
        state: string;
        wagons: Array<{ id: number }>;
        weighments?: WeighmentRecord[];
    };
    disabled: boolean;
}

export function WeighmentWorkflow({ rake, disabled }: WeighmentWorkflowProps) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const [pdfFile, setPdfFile] = useState<File | null>(null);
    
    const { data, setData, post, processing, reset } = useForm({
        weighment_pdf: null as File | null,
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

        post(`/rakes/${rake.id}/weighments`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setPdfFile(null);
            },
        });
    };

    const latestWeighment = useMemo(() => {
        if (!rake.weighments || rake.weighments.length === 0) {
            return undefined;
        }

        return [...rake.weighments].sort((a, b) => a.attempt_no - b.attempt_no)[rake.weighments.length - 1];
    }, [rake.weighments]);
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

                        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div
                                className={`flex items-center gap-2 text-sm ${
                                    isCompleted ? 'text-green-600' : 'text-orange-600'
                                }`}
                            >
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
                            <div className="flex items-center gap-2">
                                <Link
                                    href={`/weighments/${latestWeighment.id}`}
                                    className="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-medium hover:bg-muted"
                                >
                                    <Eye className="mr-1.5 h-3.5 w-3.5" />
                                    View data
                                </Link>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        if (!window.confirm('Delete all weighment data for this rake?')) {
                                            return;
                                        }

                                        router.delete(`/rakes/${rake.id}/weighments`, {
                                            preserveScroll: true,
                                        });
                                    }}
                                    disabled={disabled || processing}
                                >
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Delete Weighment
                                </Button>
                            </div>
                        </div>
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
