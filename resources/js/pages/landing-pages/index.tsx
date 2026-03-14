import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Globe, Plus, Trash2, Sparkles } from 'lucide-react';

interface LandingPage {
    id: number;
    name: string;
    slug: string;
    headline: string | null;
    status: string;
    is_active: boolean;
}

interface Props {
    pages: { data: LandingPage[] };
}

const statusVariant: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    draft: 'secondary',
    published: 'default',
    archived: 'outline',
};

export default function LandingPagesIndex({ pages }: Props) {
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        project_name: '',
        description: '',
        target_audience: 'home buyers',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/landing-pages/generate', {
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };

    const destroy = (id: number) => {
        if (confirm('Delete this landing page?')) {
            router.delete(`/landing-pages/${id}`);
        }
    };

    const togglePublish = (page: LandingPage) => {
        router.patch(`/landing-pages/${page.id}`, {
            status: page.status === 'published' ? 'draft' : 'published',
            is_active: page.status !== 'published',
            headline: page.headline ?? '',
        });
    };

    return (
        <AppLayout>
            <Head title="Landing Pages" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">AI Landing Pages</h1>
                        <p className="text-muted-foreground text-sm">Generate and manage AI-powered landing pages for your campaigns.</p>
                    </div>
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button data-pan="landing-page-generate-btn">
                                <Sparkles className="mr-2 h-4 w-4" /> Generate Page
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-md">
                            <DialogHeader>
                                <DialogTitle className="flex items-center gap-2">
                                    <Sparkles className="h-4 w-4 text-orange-500" /> Generate Landing Page
                                </DialogTitle>
                            </DialogHeader>
                            <form onSubmit={submit} className="space-y-4">
                                <div>
                                    <Label>Project / Development Name</Label>
                                    <Input
                                        value={data.project_name}
                                        onChange={(e) => setData('project_name', e.target.value)}
                                        placeholder="e.g. Harbour Views Estate"
                                    />
                                    {errors.project_name && <p className="text-destructive text-xs">{errors.project_name}</p>}
                                </div>
                                <div>
                                    <Label>Description</Label>
                                    <Textarea
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={3}
                                        placeholder="Describe the development, key features, location..."
                                    />
                                    {errors.description && <p className="text-destructive text-xs">{errors.description}</p>}
                                </div>
                                <div>
                                    <Label>Target Audience</Label>
                                    <Input
                                        value={data.target_audience}
                                        onChange={(e) => setData('target_audience', e.target.value)}
                                        placeholder="e.g. first home buyers, investors"
                                    />
                                </div>
                                <p className="text-muted-foreground flex items-center gap-1 text-xs">
                                    <Sparkles className="h-3 w-3" /> AI will generate headline, copy, and HTML content.
                                </p>
                                <Button type="submit" disabled={processing} className="w-full">
                                    {processing ? 'Generating...' : 'Generate Landing Page'}
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {pages.data.map((page) => (
                        <Card key={page.id} className="group">
                            <CardHeader className="pb-2">
                                <div className="flex items-start justify-between">
                                    <div className="flex items-center gap-2">
                                        <Globe className="h-4 w-4 text-orange-500" />
                                        <CardTitle className="text-sm">{page.name}</CardTitle>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-7 w-7 opacity-0 group-hover:opacity-100"
                                        onClick={() => destroy(page.id)}
                                    >
                                        <Trash2 className="h-3 w-3 text-red-500" />
                                    </Button>
                                </div>
                                <Badge variant={statusVariant[page.status] ?? 'secondary'} className="w-fit">
                                    {page.status}
                                </Badge>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {page.headline && <p className="line-clamp-2 text-sm font-medium">{page.headline}</p>}
                                <code className="text-muted-foreground text-xs">/{page.slug}</code>
                                <div className="flex gap-2 pt-1">
                                    <Button size="sm" variant="outline" className="flex-1" onClick={() => togglePublish(page)}>
                                        {page.status === 'published' ? 'Unpublish' : 'Publish'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                    {pages.data.length === 0 && (
                        <div className="text-muted-foreground col-span-full py-12 text-center">
                            <Globe className="mx-auto mb-2 h-8 w-8 opacity-30" />
                            <p>No landing pages yet. Generate your first AI-powered page.</p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
