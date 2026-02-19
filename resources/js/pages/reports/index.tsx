import Heading from '@/components/heading';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { BarChart3, FileDown } from 'lucide-react';
import { useState } from 'react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Reports {
    [key: string]: { name: string; description: string };
}

interface Props {
    reports: Reports;
    sidings: Siding[];
}

export default function ReportsIndex({ reports, sidings }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Reports', href: '/reports' },
    ];
    const [selectedKey, setSelectedKey] = useState<string | null>(null);
    const [params, setParams] = useState({ siding_id: '', date_from: '', date_to: '' });
    const [result, setResult] = useState<Record<string, unknown>[] | null>(null);
    const [loading, setLoading] = useState(false);

    const openGenerate = (key: string) => {
        setSelectedKey(key);
        setResult(null);
        setParams({ siding_id: '', date_from: '', date_to: '' });
    };

    const runGenerate = (exportCsv: boolean) => {
        if (!selectedKey) return;
        setLoading(true);
        const body = {
            key: selectedKey,
            siding_id: params.siding_id ? Number(params.siding_id) : null,
            date_from: params.date_from || null,
            date_to: params.date_to || null,
            export_csv: exportCsv,
        };
        if (exportCsv) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/reports/generate';
            form.target = '_blank';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrf) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = csrf;
                form.appendChild(input);
            }
            ['key', 'siding_id', 'date_from', 'date_to', 'export_csv'].forEach((name) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = String((body as Record<string, unknown>)[name] ?? '');
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            setLoading(false);
            return;
        }
        fetch('/reports/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        })
            .then((res) => res.json())
            .then((json: { data?: Record<string, unknown>[] }) => setResult(json.data ?? null))
            .catch(() => setResult(null))
            .finally(() => setLoading(false));
    };

    const reportKeys = Object.keys(reports);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reports" />
            <div className="space-y-6">
                <Heading
                    title="Reports"
                    description="Generate and export RRMCS reports"
                />
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    {reportKeys.map((key) => (
                        <Card key={key}>
                            <CardHeader>
                                <CardTitle className="text-base">{reports[key].name}</CardTitle>
                                <CardDescription>{reports[key].description}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="w-full"
                                    onClick={() => openGenerate(key)}
                                >
                                    <BarChart3 className="mr-2 size-4" />
                                    Generate
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {selectedKey && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Generate: {reports[selectedKey]?.name}</CardTitle>
                            <CardDescription>Set parameters and run or export CSV</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-wrap gap-4">
                                <div className="grid gap-2">
                                    <Label>Siding</Label>
                                    <select
                                        value={params.siding_id}
                                        onChange={(e) =>
                                            setParams((p) => ({ ...p, siding_id: e.target.value }))
                                        }
                                        className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                    >
                                        <option value="">All</option>
                                        {sidings.map((s) => (
                                            <option key={s.id} value={s.id}>
                                                {s.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="grid gap-2">
                                    <Label>Date from</Label>
                                    <Input
                                        type="date"
                                        value={params.date_from}
                                        onChange={(e) =>
                                            setParams((p) => ({ ...p, date_from: e.target.value }))
                                        }
                                        className="w-40"
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label>Date to</Label>
                                    <Input
                                        type="date"
                                        value={params.date_to}
                                        onChange={(e) =>
                                            setParams((p) => ({ ...p, date_to: e.target.value }))
                                        }
                                        className="w-40"
                                    />
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    onClick={() => runGenerate(false)}
                                    disabled={loading}
                                >
                                    Run report
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => runGenerate(true)}
                                    disabled={loading}
                                >
                                    <FileDown className="mr-2 size-4" />
                                    Export CSV
                                </Button>
                                <Button
                                    variant="ghost"
                                    onClick={() => setSelectedKey(null)}
                                >
                                    Close
                                </Button>
                            </div>
                            {result && (
                                <div className="overflow-x-auto rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                {result.length > 0 &&
                                                    Object.keys(result[0]).map((h) => (
                                                        <th
                                                            key={h}
                                                            className="px-5 py-3.5 text-left font-medium"
                                                        >
                                                            {h}
                                                        </th>
                                                    ))}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {result.map((row, i) => (
                                                <tr key={i} className="border-b last:border-0 hover:bg-muted/30">
                                                    {Object.values(row).map((v, j) => (
                                                        <td key={j} className="px-5 py-3.5">
                                                            {String(v ?? '')}
                                                        </td>
                                                    ))}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                            {result && result.length === 0 && (
                                <p className="text-sm text-muted-foreground">No rows returned.</p>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
