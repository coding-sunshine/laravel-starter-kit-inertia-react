import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { CheckCircle, FileImage, Loader2, Upload, XCircle } from 'lucide-react';
import { useRef, useState } from 'react';

interface Project {
    id: number;
    title: string;
    stage: string;
}

interface ExtractionResult {
    stored_path: string;
    file_name: string;
    file_size: number;
    mime_type: string;
    extraction: {
        status: string;
        image_type?: string;
        detected_content?: {
            has_facade_photo?: boolean;
            has_floor_plan?: boolean;
            has_site_plan?: boolean;
            has_price_list?: boolean;
            property_type?: string;
            bedrooms?: number;
            bathrooms?: number;
            estimated_price?: number;
            market_summary?: string;
            description?: string;
        };
        extraction_notes?: string;
    };
    media_attached: Array<{ type: string; collection: string }>;
}

interface Props {
    projects: Project[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: 'Brochure Extraction', href: '/brochure-extraction' },
];

export default function BrochureExtractionPage({ projects }: Props) {
    const fileRef = useRef<HTMLInputElement>(null);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [projectId, setProjectId] = useState<string>('');
    const [isUploading, setIsUploading] = useState(false);
    const [result, setResult] = useState<ExtractionResult | null>(null);
    const [error, setError] = useState<string | null>(null);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setSelectedFile(file);
            setResult(null);
            setError(null);
        }
    };

    const handleExtract = async () => {
        if (!selectedFile) return;

        setIsUploading(true);
        setError(null);

        const formData = new FormData();
        formData.append('file', selectedFile);
        if (projectId) formData.append('project_id', projectId);

        try {
            const res = await axios.post('/brochure-extraction/extract', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            setResult(res.data.result);
        } catch (err: unknown) {
            const message = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            setError(message || 'Extraction failed. Please try again.');
        } finally {
            setIsUploading(false);
        }
    };

    const formatFileSize = (bytes: number) => {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    };

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Brochure Extraction" />

            <div className="space-y-6 p-6">
                <div className="flex items-center gap-3">
                    <FileImage className="text-primary h-6 w-6" />
                    <div>
                        <h1 className="text-2xl font-bold">AI Brochure Extraction</h1>
                        <p className="text-muted-foreground text-sm">
                            Upload a brochure PDF or image to extract facade photos, floor plans, and property details via AI.
                        </p>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Upload Panel */}
                    <div className="rounded-lg border bg-card p-5">
                        <h2 className="mb-4 font-semibold">Upload Brochure</h2>

                        {/* Drop Zone */}
                        <div
                            onClick={() => fileRef.current?.click()}
                            className="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-border p-8 transition-colors hover:border-primary hover:bg-accent/30"
                        >
                            <Upload className="text-muted-foreground mb-3 h-10 w-10" />
                            <p className="text-sm font-medium">Click to upload or drag and drop</p>
                            <p className="text-muted-foreground mt-1 text-xs">PDF, JPG, PNG, WEBP up to 20MB</p>
                            <input
                                ref={fileRef}
                                type="file"
                                className="hidden"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                onChange={handleFileChange}
                            />
                        </div>

                        {selectedFile && (
                            <div className="mt-3 flex items-center gap-3 rounded-md bg-muted/50 p-3">
                                <FileImage className="text-primary h-5 w-5 shrink-0" />
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">{selectedFile.name}</p>
                                    <p className="text-muted-foreground text-xs">{formatFileSize(selectedFile.size)}</p>
                                </div>
                            </div>
                        )}

                        <div className="mt-4">
                            <label className="text-muted-foreground mb-1 block text-xs font-medium">Attach to Project (optional)</label>
                            <select
                                value={projectId}
                                onChange={(e) => setProjectId(e.target.value)}
                                className="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <option value="">— No project —</option>
                                {projects.map((p) => (
                                    <option key={p.id} value={p.id}>{p.title}</option>
                                ))}
                            </select>
                        </div>

                        <button
                            onClick={handleExtract}
                            disabled={!selectedFile || isUploading}
                            className="mt-4 w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        >
                            {isUploading ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    Extracting…
                                </>
                            ) : (
                                <>
                                    <FileImage className="h-4 w-4" />
                                    Extract Media
                                </>
                            )}
                        </button>

                        {error && (
                            <div className="mt-3 flex items-center gap-2 rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                                <XCircle className="h-4 w-4 shrink-0" />
                                {error}
                            </div>
                        )}
                    </div>

                    {/* Results Panel */}
                    {result && (
                        <div className="rounded-lg border bg-card p-5">
                            <div className="mb-4 flex items-center gap-2">
                                <CheckCircle className="h-5 w-5 text-green-500" />
                                <h2 className="font-semibold">Extraction Results</h2>
                            </div>

                            <div className="space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">File</span>
                                    <span className="font-medium truncate max-w-[200px]">{result.file_name}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Status</span>
                                    <span className={`font-medium ${result.extraction.status === 'extracted' ? 'text-green-600' : 'text-muted-foreground'}`}>
                                        {result.extraction.status}
                                    </span>
                                </div>

                                {result.extraction.detected_content && (
                                    <>
                                        {result.extraction.detected_content.property_type && (
                                            <div className="flex justify-between">
                                                <span className="text-muted-foreground">Property Type</span>
                                                <span className="font-medium capitalize">{result.extraction.detected_content.property_type}</span>
                                            </div>
                                        )}
                                        {result.extraction.detected_content.bedrooms !== undefined && (
                                            <div className="flex justify-between">
                                                <span className="text-muted-foreground">Bedrooms</span>
                                                <span className="font-medium">{result.extraction.detected_content.bedrooms ?? '—'}</span>
                                            </div>
                                        )}
                                        {result.extraction.detected_content.estimated_price && (
                                            <div className="flex justify-between">
                                                <span className="text-muted-foreground">Est. Price</span>
                                                <span className="font-medium">
                                                    {new Intl.NumberFormat('en-AU', { style: 'currency', currency: 'AUD', maximumFractionDigits: 0 }).format(result.extraction.detected_content.estimated_price)}
                                                </span>
                                            </div>
                                        )}

                                        {/* Boolean flags */}
                                        <div className="mt-3 grid grid-cols-2 gap-2">
                                            {[
                                                { key: 'has_facade_photo', label: 'Facade Photo' },
                                                { key: 'has_floor_plan', label: 'Floor Plan' },
                                                { key: 'has_site_plan', label: 'Site Plan' },
                                                { key: 'has_price_list', label: 'Price List' },
                                            ].map(({ key, label }) => {
                                                const val = result.extraction.detected_content?.[key as keyof typeof result.extraction.detected_content];
                                                return (
                                                    <div key={key} className="flex items-center gap-1.5">
                                                        {val ? (
                                                            <CheckCircle className="h-4 w-4 text-green-500" />
                                                        ) : (
                                                            <XCircle className="h-4 w-4 text-muted-foreground/40" />
                                                        )}
                                                        <span className={val ? '' : 'text-muted-foreground'}>{label}</span>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </>
                                )}

                                {result.extraction.extraction_notes && (
                                    <div className="mt-3 rounded-md bg-muted/50 p-3">
                                        <p className="text-muted-foreground text-xs font-medium mb-1">Notes</p>
                                        <p className="text-sm">{result.extraction.extraction_notes}</p>
                                    </div>
                                )}

                                {result.media_attached.length > 0 && (
                                    <div className="mt-3">
                                        <p className="text-muted-foreground text-xs font-medium mb-2">Media Attached</p>
                                        {result.media_attached.map((m, i) => (
                                            <div key={i} className="flex items-center gap-1.5 text-xs">
                                                <CheckCircle className="h-3 w-3 text-green-500" />
                                                <span>{m.type} → {m.collection}</span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {!result && !isUploading && (
                        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed bg-muted/20 p-8">
                            <FileImage className="text-muted-foreground/30 h-16 w-16 mb-3" />
                            <p className="text-muted-foreground text-sm">Upload a brochure to see extraction results</p>
                        </div>
                    )}
                </div>
            </div>
        </AppSidebarLayout>
    );
}
