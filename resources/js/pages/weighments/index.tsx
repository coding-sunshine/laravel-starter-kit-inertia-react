import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Upload, Scale, Eye } from 'lucide-react';
import { useRef, useState } from 'react';

interface RakeWeighmentData {
    id: number;
    rake_id: number;
    attempt_no: number;
    gross_weighment_datetime: string | null;
    tare_weighment_datetime: string | null;
    train_name: string | null;
    direction: string | null;
    commodity: string | null;
    from_station: string | null;
    to_station: string | null;
    priority_number: string | null;
    pdf_file_path: string | null;
    status: string;
    created_by: number | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    weighments?: RakeWeighmentData[];
}

export default function WeighmentsIndex({ weighments = [] }: Props) {
    const { flash, errors } = usePage<{
        flash?: { success?: string };
        errors?: { pdf?: string };
    }>().props;

    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Weighments', href: '/weighments' },
    ];

    const handleUploadClick = () => {
        fileInputRef.current?.click();
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) {
            return;
        }

        setUploading(true);
        const formData = new FormData();
        formData.append('pdf', file);

        router.post('/weighments/import', formData, {
            forceFormData: true,
            onFinish: () => {
                setUploading(false);
                e.target.value = '';
            },
        });
    };

    const handleView = (id: number) => {
        router.visit(`/weighments/${id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Weighments" />

            <div className="space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                        {flash.success}
                    </div>
                )}

                {errors?.pdf && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                        {errors.pdf}
                    </div>
                )}

                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Weighments</h1>
                        <p className="text-muted-foreground">
                            Manage historical rake wagon weighment data
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept=".pdf"
                            className="hidden"
                            onChange={handleFileChange}
                        />
                        <Button
                            onClick={handleUploadClick}
                            disabled={uploading}
                            data-pan="weighments-upload-pdf-button"
                            className="flex items-center gap-2"
                        >
                            <Upload className="h-4 w-4" />
                            {uploading ? 'Uploading…' : 'Upload Document'}
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Scale className="h-5 w-5" />
                            Rake Weighments
                        </CardTitle>
                        <CardDescription>
                            View and manage rake weighment data from uploaded documents
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {weighments.length === 0 ? (
                            <div className="text-center py-8">
                                <Scale className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                <h3 className="text-lg font-medium mb-2">No weighment data</h3>
                                <p className="text-muted-foreground mb-4">
                                    Upload a document to start viewing weighment data
                                </p>
                                <Button onClick={handleUploadClick} variant="outline">
                                    <Upload className="h-4 w-4 mr-2" />
                                    Upload First Document
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full border-collapse">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left p-2">Train Name</th>
                                            <th className="text-left p-2">Direction</th>
                                            <th className="text-left p-2">Commodity</th>
                                            <th className="text-left p-2">From Station</th>
                                            <th className="text-left p-2">To Station</th>
                                            <th className="text-left p-2">Priority Number</th>
                                            <th className="text-left p-2">Gross Weighment</th>
                                            <th className="text-left p-2">Tare Weighment</th>
                                            <th className="text-left p-2">Attempt No</th>
                                            <th className="text-left p-2">Status</th>
                                            <th className="text-left p-2">Created At</th>
                                            <th className="text-left p-2">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {weighments.map((weighment) => (
                                            <tr key={weighment.id} className="border-b hover:bg-muted/50">
                                                <td className="p-2">{weighment.train_name || '-'}</td>
                                                <td className="p-2">{weighment.direction || '-'}</td>
                                                <td className="p-2">{weighment.commodity || '-'}</td>
                                                <td className="p-2">{weighment.from_station || '-'}</td>
                                                <td className="p-2">{weighment.to_station || '-'}</td>
                                                <td className="p-2">{weighment.priority_number || '-'}</td>
                                                <td className="p-2">
                                                    {weighment.gross_weighment_datetime
                                                        ? new Date(weighment.gross_weighment_datetime).toLocaleString()
                                                        : '-'}
                                                </td>
                                                <td className="p-2">
                                                    {weighment.tare_weighment_datetime
                                                        ? new Date(weighment.tare_weighment_datetime).toLocaleString()
                                                        : '-'}
                                                </td>
                                                <td className="p-2">{weighment.attempt_no}</td>
                                                <td className="p-2">
                                                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                                        weighment.status === 'success' 
                                                            ? 'bg-green-100 text-green-800' 
                                                            : 'bg-red-100 text-red-800'
                                                    }`}>
                                                        {weighment.status}
                                                    </span>
                                                </td>
                                                <td className="p-2">
                                                    {new Date(weighment.created_at).toLocaleString()}
                                                </td>
                                                <td className="p-2">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="inline-flex items-center gap-1"
                                                        onClick={() => handleView(weighment.id)}
                                                    >
                                                        <Eye className="h-3 w-3" />
                                                        View
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
