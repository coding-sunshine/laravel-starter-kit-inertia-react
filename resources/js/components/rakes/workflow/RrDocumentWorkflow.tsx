import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { FileText, CheckCircle, Clock, Download, Eye } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';

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
    const {
        props: { flash },
    } = usePage<{ flash?: { success?: string; rr_document_id?: number } }>();

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
                    <div className="text-sm text-muted-foreground">
                        No RR document uploaded yet. Use the RR upload section above to attach a Railway Receipt PDF to this rake.
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
                            Railway receipt document linked to this rake
                        </div>

                        <div className="flex justify-end gap-2">
                            <Button asChild variant="outline" size="sm">
                                <Link href={`/railway-receipts/${rrDocument.id}`}>
                                    <Eye className="mr-2 h-4 w-4" />
                                    View RR
                                </Link>
                            </Button>
                            <Button asChild variant="outline" size="sm">
                                <a
                                    href={`/railway-receipts/${rrDocument.id}/pdf`}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <Download className="mr-2 h-4 w-4" />
                                    Download RR PDF
                                </a>
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
