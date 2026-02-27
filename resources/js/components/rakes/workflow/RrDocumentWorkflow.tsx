import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { FileText, CheckCircle, Clock, AlertTriangle, Download } from 'lucide-react';
import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface RrDocumentRecord {
    id: number;
    rr_number: string;
    rr_received_date: string;
    rr_weight_mt: string | null;
    document_status: string;
}

interface RrDocumentWorkflowProps {
    rake: {
        id: number;
        state: string;
        rrDocuments?: RrDocumentRecord[];
    };
    disabled: boolean;
}

export function RrDocumentWorkflow({ rake, disabled }: RrDocumentWorkflowProps) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const { data, setData, post, processing, reset } = useForm({
        rr_number: '',
        rr_received_date: new Date().toISOString().slice(0, 10),
        rr_weight_mt: '',
    });

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(`/railway-receipts`, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
            },
        });
    };

    const rrDocument = rake.rrDocuments?.[0];
    const hasRrDocument = !!rrDocument;

    const getStatusIcon = () => {
        if (!hasRrDocument) return <Clock className="h-4 w-4" />;
        return <CheckCircle className="h-4 w-4 text-green-600" />;
    };

    const getStatusText = () => {
        if (!hasRrDocument) return 'Not Created';
        return 'Created';
    };

    const getStatusVariant = () => {
        if (!hasRrDocument) return "secondary";
        return "default";
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <FileText className="h-5 w-5" />
                        Railway Receipt (RR) Document
                    </div>
                    <div className="flex items-center gap-2">
                        {getStatusIcon()}
                        <Badge variant={getStatusVariant()}>
                            {getStatusText()}
                        </Badge>
                    </div>
                </CardTitle>
                <CardDescription>
                    Create official railway receipt document
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {!hasRrDocument ? (
                    <div>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="rr_number">RR Number</Label>
                                <Input
                                    id="rr_number"
                                    name="rr_number"
                                    value={data.rr_number}
                                    onChange={(e) => setData('rr_number', e.target.value)}
                                    placeholder="Enter railway receipt number"
                                    required
                                    disabled={disabled}
                                />
                                <InputError message={errors?.rr_number} />
                            </div>
                            
                            <div>
                                <Label htmlFor="rr_received_date">RR Received Date</Label>
                                <Input
                                    id="rr_received_date"
                                    name="rr_received_date"
                                    type="date"
                                    value={data.rr_received_date}
                                    onChange={(e) => setData('rr_received_date', e.target.value)}
                                    required
                                    disabled={disabled}
                                />
                                <InputError message={errors?.rr_received_date} />
                            </div>

                            <div>
                                <Label htmlFor="rr_weight_mt">RR Weight (MT)</Label>
                                <Input
                                    id="rr_weight_mt"
                                    name="rr_weight_mt"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.rr_weight_mt}
                                    onChange={(e) => setData('rr_weight_mt', e.target.value)}
                                    placeholder="Weight as per RR document"
                                    disabled={disabled}
                                />
                                <p className="text-xs text-muted-foreground mt-1">
                                    Optional - weight mentioned in RR document
                                </p>
                                <InputError message={errors?.rr_weight_mt} />
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
                                    <FileText className="mr-2 h-4 w-4" />
                                    Create RR Document
                                </Button>
                            </div>
                        </form>
                    </div>
                ) : (
                    <div className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>RR Number</Label>
                                <p className="text-lg font-bold">{rrDocument.rr_number}</p>
                            </div>
                            <div>
                                <Label>Received Date</Label>
                                <p className="text-sm">
                                    {new Date(rrDocument.rr_received_date).toLocaleDateString()}
                                </p>
                            </div>
                        </div>

                        {rrDocument.rr_weight_mt && (
                            <div>
                                <Label>RR Weight</Label>
                                <p className="text-lg font-bold">{rrDocument.rr_weight_mt} MT</p>
                            </div>
                        )}

                        <div>
                            <Label>Document Status</Label>
                            <Badge variant="default">
                                {rrDocument.document_status.replace('_', ' ').toUpperCase()}
                            </Badge>
                        </div>

                        <div className="flex items-center gap-2 text-sm text-green-600">
                            <CheckCircle className="h-4 w-4" />
                            Railway receipt document created successfully
                        </div>

                        <div className="flex justify-end">
                            <Button variant="outline" size="sm">
                                <Download className="mr-2 h-4 w-4" />
                                Download RR
                            </Button>
                        </div>
                    </div>
                )}

                {disabled && !hasRrDocument && (
                    <div className="text-center py-4 text-sm text-muted-foreground">
                        Complete weighment to enable RR document creation
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
