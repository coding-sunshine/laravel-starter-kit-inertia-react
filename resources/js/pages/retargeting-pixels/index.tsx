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
import { Target, Plus, Trash2, ToggleLeft, ToggleRight } from 'lucide-react';

interface RetargetingPixel {
    id: number;
    name: string;
    platform: string;
    pixel_id: string;
    status: string;
}

interface Props {
    pixels: { data: RetargetingPixel[] };
}

export default function RetargetingPixelsIndex({ pixels }: Props) {
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        platform: 'facebook',
        pixel_id: '',
        script_tag: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/retargeting-pixels', {
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };

    const toggleStatus = (pixel: RetargetingPixel) => {
        router.patch(`/retargeting-pixels/${pixel.id}`, {
            name: pixel.name,
            status: pixel.status === 'active' ? 'paused' : 'active',
        });
    };

    const destroy = (id: number) => {
        if (confirm('Remove this pixel?')) {
            router.delete(`/retargeting-pixels/${id}`);
        }
    };

    const platformColor: Record<string, string> = {
        facebook: 'bg-blue-100 text-blue-800',
        google: 'bg-green-100 text-green-800',
        tiktok: 'bg-gray-900 text-white',
        linkedin: 'bg-indigo-100 text-indigo-800',
        twitter: 'bg-sky-100 text-sky-800',
    };

    return (
        <AppLayout>
            <Head title="Retargeting Pixels" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Retargeting Pixels</h1>
                        <p className="text-muted-foreground text-sm">Manage tracking pixels for ad retargeting campaigns.</p>
                    </div>
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button data-pan="retargeting-pixel-add-btn">
                                <Plus className="mr-2 h-4 w-4" /> Add Pixel
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-md">
                            <DialogHeader>
                                <DialogTitle>Add Retargeting Pixel</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={submit} className="space-y-4">
                                <div>
                                    <Label>Name</Label>
                                    <Input value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="e.g. Facebook Main Pixel" />
                                    {errors.name && <p className="text-destructive text-xs">{errors.name}</p>}
                                </div>
                                <div>
                                    <Label>Platform</Label>
                                    <Select value={data.platform} onValueChange={(v) => setData('platform', v)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {['facebook', 'google', 'tiktok', 'linkedin', 'twitter'].map((p) => (
                                                <SelectItem key={p} value={p}>
                                                    {p.charAt(0).toUpperCase() + p.slice(1)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label>Pixel ID</Label>
                                    <Input value={data.pixel_id} onChange={(e) => setData('pixel_id', e.target.value)} placeholder="123456789" />
                                    {errors.pixel_id && <p className="text-destructive text-xs">{errors.pixel_id}</p>}
                                </div>
                                <Button type="submit" disabled={processing} className="w-full">
                                    {processing ? 'Adding...' : 'Add Pixel'}
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {pixels.data.map((pixel) => (
                        <Card key={pixel.id} className="group">
                            <CardHeader className="pb-2">
                                <div className="flex items-start justify-between">
                                    <div className="flex items-center gap-2">
                                        <Target className="h-4 w-4 text-orange-500" />
                                        <CardTitle className="text-sm">{pixel.name}</CardTitle>
                                    </div>
                                    <div className="flex gap-1 opacity-0 group-hover:opacity-100">
                                        <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => toggleStatus(pixel)}>
                                            {pixel.status === 'active' ? (
                                                <ToggleRight className="h-4 w-4 text-green-600" />
                                            ) : (
                                                <ToggleLeft className="h-4 w-4 text-gray-400" />
                                            )}
                                        </Button>
                                        <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => destroy(pixel.id)}>
                                            <Trash2 className="h-3 w-3 text-red-500" />
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${platformColor[pixel.platform] ?? 'bg-gray-100'}`}>
                                        {pixel.platform}
                                    </span>
                                    <code className="rounded bg-gray-100 px-1 text-xs">{pixel.pixel_id}</code>
                                    <Badge variant={pixel.status === 'active' ? 'default' : 'secondary'}>{pixel.status}</Badge>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                    {pixels.data.length === 0 && (
                        <div className="text-muted-foreground col-span-full py-12 text-center">
                            <Target className="mx-auto mb-2 h-8 w-8 opacity-30" />
                            <p>No pixels configured yet.</p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
