import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { BookOpen, Plus, FileText } from 'lucide-react';

interface BrochureLayout {
    id: number;
    name: string;
    description: string | null;
    template_type: string;
    is_default: boolean;
    is_active: boolean;
}

interface Flyer {
    id: number;
    project?: { name: string };
    lot?: { lot_number: string };
}

interface Props {
    layouts: { data: BrochureLayout[] };
    flyers: Flyer[];
}

export default function BrochureLayoutsIndex({ layouts, flyers }: Props) {
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        template_type: 'puck',
        is_default: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/brochure-layouts', {
            onSuccess: () => setOpen(false),
        });
    };

    const generatePdf = (flyerId: number) => {
        router.post(
            `/brochure-layouts/flyers/${flyerId}/generate-pdf`,
            {},
            {
                onSuccess: () => alert('PDF generated successfully!'),
            },
        );
    };

    return (
        <AppLayout>
            <Head title="Brochure Layouts (v2)" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Brochure Builder v2</h1>
                        <p className="text-muted-foreground text-sm">AI-powered brochure layouts and PDF generation.</p>
                    </div>
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button data-pan="brochure-layout-create-btn">
                                <Plus className="mr-2 h-4 w-4" /> New Layout
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-md">
                            <DialogHeader>
                                <DialogTitle>Create Brochure Layout</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={submit} className="space-y-4">
                                <div>
                                    <Label>Layout Name</Label>
                                    <Input value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="e.g. Modern A4 Landscape" />
                                    {errors.name && <p className="text-destructive text-xs">{errors.name}</p>}
                                </div>
                                <div>
                                    <Label>Description</Label>
                                    <Input
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Optional description"
                                    />
                                </div>
                                <div>
                                    <Label>Template Type</Label>
                                    <Select value={data.template_type} onValueChange={(v) => setData('template_type', v)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="puck">Puck Builder</SelectItem>
                                            <SelectItem value="blade">Blade Template</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="is_default"
                                        checked={data.is_default}
                                        onChange={(e) => setData('is_default', e.target.checked)}
                                    />
                                    <Label htmlFor="is_default" className="cursor-pointer">
                                        Set as default layout
                                    </Label>
                                </div>
                                <Button type="submit" disabled={processing} className="w-full">
                                    {processing ? 'Creating...' : 'Create Layout'}
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <h2 className="mb-3 font-semibold">Layouts</h2>
                        <div className="space-y-3">
                            {layouts.data.map((layout) => (
                                <Card key={layout.id}>
                                    <CardHeader className="pb-1">
                                        <CardTitle className="flex items-center gap-2 text-sm">
                                            <BookOpen className="h-4 w-4 text-orange-500" />
                                            {layout.name}
                                            {layout.is_default && (
                                                <span className="rounded bg-orange-100 px-1 text-xs text-orange-800">Default</span>
                                            )}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        {layout.description && <p className="text-muted-foreground text-xs">{layout.description}</p>}
                                        <span className="mt-1 inline-block rounded bg-gray-100 px-2 py-0.5 text-xs">{layout.template_type}</span>
                                    </CardContent>
                                </Card>
                            ))}
                            {layouts.data.length === 0 && <p className="text-muted-foreground text-sm">No layouts yet.</p>}
                        </div>
                    </div>

                    <div>
                        <h2 className="mb-3 font-semibold">Generate Brochure PDF (v2)</h2>
                        <div className="space-y-3">
                            {flyers.map((flyer) => (
                                <Card key={flyer.id} className="flex items-center justify-between p-3">
                                    <div className="flex items-center gap-2">
                                        <FileText className="h-4 w-4 text-gray-500" />
                                        <span className="text-sm">
                                            {flyer.project?.name ?? 'Flyer'} {flyer.lot ? `— Lot ${flyer.lot.lot_number}` : ''}
                                        </span>
                                    </div>
                                    <Button size="sm" variant="outline" onClick={() => generatePdf(flyer.id)}>
                                        Generate PDF
                                    </Button>
                                </Card>
                            ))}
                            {flyers.length === 0 && <p className="text-muted-foreground text-sm">No flyers found.</p>}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
