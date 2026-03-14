import { Head, useForm, usePage } from '@inertiajs/react';
import { Globe, Loader2, Plus, Trash2 } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Websites', href: '/website-index' },
];

interface Website {
    id: number;
    title: string;
    url: string | null;
    site_type: string;
    stage: number;
    created_at: string;
}

interface PageProps {
    websites: Record<string, Website[]>;
}

const SITE_SLOTS = [
    { key: 'php_standard', label: 'PHP Standard', description: 'Standard PHP website' },
    { key: 'php_premium', label: 'PHP Premium', description: 'Premium PHP website with advanced features' },
    { key: 'wp_real_estate', label: 'WP Real Estate', description: 'WordPress real estate portal' },
    { key: 'wp_wealth_creation', label: 'WP Wealth Creation', description: 'WordPress wealth creation site' },
    { key: 'wp_finance', label: 'WP Finance', description: 'WordPress finance portal' },
];

function stageBadge(stage: number) {
    switch (stage) {
        case 1:
            return <Badge variant="outline">Pending</Badge>;
        case 2:
            return <Badge variant="secondary">Provisioning</Badge>;
        case 3:
            return <Badge variant="default">Active</Badge>;
        case 4:
            return <Badge variant="destructive">Removing</Badge>;
        default:
            return <Badge variant="outline">Unknown</Badge>;
    }
}

function CreateWebsiteDialog({ siteType }: { siteType: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        site_type: siteType,
        primary_color: '',
        secondary_color: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/website-index', {
            onSuccess: () => reset(),
        });
    }

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    <Plus className="mr-1 h-4 w-4" />
                    Create
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create Website</DialogTitle>
                    <DialogDescription>Set up a new website for this slot.</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1">
                        <Label htmlFor="title">Site Title</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder="My Real Estate Site"
                        />
                        {errors.title && <p className="text-destructive text-sm">{errors.title}</p>}
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="primary_color">Primary Color</Label>
                        <Input
                            id="primary_color"
                            value={data.primary_color}
                            onChange={(e) => setData('primary_color', e.target.value)}
                            placeholder="#0066cc"
                        />
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="secondary_color">Secondary Color</Label>
                        <Input
                            id="secondary_color"
                            value={data.secondary_color}
                            onChange={(e) => setData('secondary_color', e.target.value)}
                            placeholder="#ff6600"
                        />
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Create Website
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function WebsiteSlotCard({ slot, website }: { slot: (typeof SITE_SLOTS)[0]; website?: Website }) {
    const { delete: destroy, processing } = useForm({});

    function handleDelete() {
        if (website && confirm('Are you sure you want to remove this website?')) {
            destroy(`/website-index/${website.id}`);
        }
    }

    return (
        <Card>
            <CardHeader>
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-2">
                        <Globe className="text-muted-foreground h-5 w-5" />
                        <div>
                            <CardTitle className="text-base">{slot.label}</CardTitle>
                            <CardDescription className="text-xs">{slot.description}</CardDescription>
                        </div>
                    </div>
                    {website && stageBadge(website.stage)}
                </div>
            </CardHeader>
            {website && (
                <CardContent className="space-y-1">
                    <p className="text-sm font-medium">{website.title}</p>
                    {website.url && (
                        <a
                            href={website.url}
                            target="_blank"
                            rel="noreferrer"
                            className="text-primary truncate text-xs hover:underline"
                        >
                            {website.url}
                        </a>
                    )}
                    {!website.url && website.stage < 3 && (
                        <p className="text-muted-foreground text-xs">Provisioning in progress…</p>
                    )}
                </CardContent>
            )}
            <CardFooter className="justify-end gap-2">
                {website ? (
                    <Button variant="ghost" size="sm" onClick={handleDelete} disabled={processing}>
                        <Trash2 className="h-4 w-4" />
                    </Button>
                ) : (
                    <CreateWebsiteDialog siteType={slot.key} />
                )}
            </CardFooter>
        </Card>
    );
}

export default function WebsitesIndex() {
    const { websites } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Websites" />

            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold">Websites</h1>
                    <p className="text-muted-foreground text-sm">Manage your organization's websites across all available slots.</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {SITE_SLOTS.map((slot) => {
                        const existing = websites[slot.key]?.[0];

                        return <WebsiteSlotCard key={slot.key} slot={slot} website={existing} />;
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
