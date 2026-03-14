import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Download, FileText, Upload } from 'lucide-react';
import { useRef, useState } from 'react';

interface Props {
    supported_formats: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventory', href: '/inventory' },
    { title: 'Import', href: '/inventory' },
];

type Tab = 'import' | 'templates';

export default function InventoryIndexPage({ supported_formats }: Props) {
    const [activeTab, setActiveTab] = useState<Tab>('import');
    const [dragOver, setDragOver] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors, progress } = useForm<{
        file: File | null;
    }>({
        file: null,
    });

    function handleFileChange(file: File | null) {
        setData('file', file);
    }

    function handleDrop(e: React.DragEvent) {
        e.preventDefault();
        setDragOver(false);
        const file = e.dataTransfer.files[0] ?? null;
        handleFileChange(file);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/inventory/import', {
            forceFormData: true,
        });
    }

    const templates = [
        { label: 'Lots CSV Template', type: 'lots', filename: 'lots-template.csv' },
        { label: 'Projects CSV Template', type: 'projects', filename: 'projects-template.csv' },
    ];

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Inventory Import" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4" data-pan="inventory-index">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Inventory Import</h1>
                    <p className="text-muted-foreground">
                        Import lots and projects from JSON or CSV files
                    </p>
                </div>

                {/* Tabs */}
                <div className="border-b">
                    <nav className="-mb-px flex gap-6">
                        {(['import', 'templates'] as Tab[]).map((tab) => (
                            <button
                                key={tab}
                                onClick={() => setActiveTab(tab)}
                                className={`border-b-2 pb-3 text-sm font-medium capitalize transition-colors ${
                                    activeTab === tab
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                {tab}
                            </button>
                        ))}
                    </nav>
                </div>

                {/* Import Tab */}
                {activeTab === 'import' && (
                    <div className="max-w-2xl">
                        <form onSubmit={handleSubmit} className="space-y-4">
                            {/* Drop zone */}
                            <div
                                onClick={() => fileInputRef.current?.click()}
                                onDrop={handleDrop}
                                onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
                                onDragLeave={() => setDragOver(false)}
                                className={`flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed p-12 text-center transition-colors ${
                                    dragOver
                                        ? 'border-primary bg-primary/5'
                                        : 'border-muted-foreground/30 hover:border-primary/50 hover:bg-muted/30'
                                }`}
                            >
                                <div className="rounded-full bg-muted p-3">
                                    <Upload className="h-6 w-6 text-muted-foreground" />
                                </div>
                                {data.file ? (
                                    <div>
                                        <p className="font-medium text-foreground">{data.file.name}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {(data.file.size / 1024).toFixed(1)} KB
                                        </p>
                                    </div>
                                ) : (
                                    <div>
                                        <p className="font-medium">Drop your file here, or click to browse</p>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            Supported formats:{' '}
                                            {supported_formats.map((f) => f.toUpperCase()).join(', ')}
                                        </p>
                                    </div>
                                )}
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept={supported_formats.map((f) => `.${f}`).join(',')}
                                    className="hidden"
                                    onChange={(e) => handleFileChange(e.target.files?.[0] ?? null)}
                                />
                            </div>

                            {errors.file && (
                                <p className="text-sm text-destructive">{errors.file}</p>
                            )}

                            {progress && (
                                <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        className="h-full rounded-full bg-primary transition-all"
                                        style={{ width: `${progress.percentage}%` }}
                                    />
                                </div>
                            )}

                            <div className="rounded-lg bg-muted/40 p-4">
                                <h3 className="mb-2 text-sm font-medium">Format Notes</h3>
                                <ul className="space-y-1 text-sm text-muted-foreground">
                                    <li>CSV files must include a header row with column names</li>
                                    <li>JSON files should be an array of objects</li>
                                    <li>Required fields vary by type — download templates below</li>
                                </ul>
                            </div>

                            <button
                                type="submit"
                                disabled={!data.file || processing}
                                className="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Upload className="h-4 w-4" />
                                {processing ? 'Importing...' : 'Import File'}
                            </button>
                        </form>
                    </div>
                )}

                {/* Templates Tab */}
                {activeTab === 'templates' && (
                    <div className="max-w-xl space-y-4">
                        <p className="text-sm text-muted-foreground">
                            Download these templates to see the required columns and example data for each type.
                        </p>
                        <div className="space-y-3">
                            {templates.map((template) => (
                                <div
                                    key={template.type}
                                    className="flex items-center justify-between rounded-lg border bg-card p-4"
                                >
                                    <div className="flex items-center gap-3">
                                        <div className="rounded-md bg-muted p-2">
                                            <FileText className="h-5 w-5 text-muted-foreground" />
                                        </div>
                                        <div>
                                            <p className="font-medium">{template.label}</p>
                                            <p className="text-xs text-muted-foreground">{template.filename}</p>
                                        </div>
                                    </div>
                                    <a
                                        href={`/inventory/templates/${template.type}`}
                                        download={template.filename}
                                        className="inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm font-medium hover:bg-accent"
                                        data-pan={`inventory-template-${template.type}`}
                                    >
                                        <Download className="h-4 w-4" />
                                        Download
                                    </a>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppSidebarLayout>
    );
}
