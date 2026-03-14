import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Megaphone, Plus, Trash2, Sparkles } from 'lucide-react';

interface AdTemplate {
    id: number;
    name: string;
    channel: string;
    type: string;
    tone: string;
    headline: string | null;
    body_copy: string | null;
    cta_text: string | null;
    is_active: boolean;
}

interface Props {
    templates: {
        data: AdTemplate[];
        current_page: number;
        last_page: number;
    };
}

export default function AdTemplatesIndex({ templates }: Props) {
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        channel: 'facebook',
        type: 'ad',
        tone: 'professional',
        context: '',
        generate_ai: true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/ad-templates', {
            onSuccess: () => setOpen(false),
        });
    };

    const destroy = (id: number) => {
        if (confirm('Delete this template?')) {
            router.delete(`/ad-templates/${id}`);
        }
    };

    const channelColor: Record<string, string> = {
        facebook: 'bg-blue-100 text-blue-800',
        instagram: 'bg-pink-100 text-pink-800',
        twitter: 'bg-sky-100 text-sky-800',
        linkedin: 'bg-indigo-100 text-indigo-800',
        google: 'bg-green-100 text-green-800',
    };

    return (
        <AppLayout>
            <Head title="Ad & Social Templates" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Ad & Social Templates</h1>
                        <p className="text-muted-foreground text-sm">Manage ad copy templates for all channels.</p>
                    </div>
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button data-pan="ad-templates-create-btn">
                                <Plus className="mr-2 h-4 w-4" />
                                New Template
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-md">
                            <DialogHeader>
                                <DialogTitle>Create Ad Template</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={submit} className="space-y-4">
                                <div>
                                    <Label>Name</Label>
                                    <Input value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Template name" />
                                    {errors.name && <p className="text-destructive text-xs">{errors.name}</p>}
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <Label>Channel</Label>
                                        <Select value={data.channel} onValueChange={(v) => setData('channel', v)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {['facebook', 'instagram', 'twitter', 'linkedin', 'google'].map((c) => (
                                                    <SelectItem key={c} value={c}>
                                                        {c.charAt(0).toUpperCase() + c.slice(1)}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label>Type</Label>
                                        <Select value={data.type} onValueChange={(v) => setData('type', v)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {['ad', 'social', 'carousel', 'story'].map((t) => (
                                                    <SelectItem key={t} value={t}>
                                                        {t.charAt(0).toUpperCase() + t.slice(1)}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                                <div>
                                    <Label>Tone</Label>
                                    <Select value={data.tone} onValueChange={(v) => setData('tone', v)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {['professional', 'casual', 'urgent', 'friendly'].map((t) => (
                                                <SelectItem key={t} value={t}>
                                                    {t.charAt(0).toUpperCase() + t.slice(1)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label>Context (for AI generation)</Label>
                                    <Input
                                        value={data.context}
                                        onChange={(e) => setData('context', e.target.value)}
                                        placeholder="e.g. 3-bed apartments in Sydney from $650k"
                                    />
                                </div>
                                <div className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="generate_ai"
                                        checked={data.generate_ai}
                                        onChange={(e) => setData('generate_ai', e.target.checked)}
                                    />
                                    <Label htmlFor="generate_ai" className="flex cursor-pointer items-center gap-1">
                                        <Sparkles className="h-3 w-3" /> Generate copy with AI
                                    </Label>
                                </div>
                                <Button type="submit" disabled={processing} className="w-full">
                                    {processing ? 'Creating...' : 'Create Template'}
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {templates.data.map((template) => (
                        <Card key={template.id} className="group relative">
                            <CardHeader className="pb-2">
                                <div className="flex items-start justify-between">
                                    <div className="flex items-center gap-2">
                                        <Megaphone className="h-4 w-4 text-orange-500" />
                                        <CardTitle className="text-sm">{template.name}</CardTitle>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-7 w-7 opacity-0 group-hover:opacity-100"
                                        onClick={() => destroy(template.id)}
                                    >
                                        <Trash2 className="h-3 w-3 text-red-500" />
                                    </Button>
                                </div>
                                <div className="flex flex-wrap gap-1">
                                    <span
                                        className={`rounded-full px-2 py-0.5 text-xs font-medium ${channelColor[template.channel] ?? 'bg-gray-100 text-gray-800'}`}
                                    >
                                        {template.channel}
                                    </span>
                                    <Badge variant="outline" className="text-xs">
                                        {template.type}
                                    </Badge>
                                    <Badge variant="outline" className="text-xs">
                                        {template.tone}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {template.headline && <p className="text-sm font-semibold">{template.headline}</p>}
                                {template.body_copy && <p className="text-muted-foreground line-clamp-2 text-xs">{template.body_copy}</p>}
                                {template.cta_text && (
                                    <span className="inline-block rounded bg-orange-100 px-2 py-0.5 text-xs text-orange-800">{template.cta_text}</span>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                    {templates.data.length === 0 && (
                        <div className="text-muted-foreground col-span-full py-12 text-center">
                            <Megaphone className="mx-auto mb-2 h-8 w-8 opacity-30" />
                            <p>No ad templates yet. Create one to get started.</p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
