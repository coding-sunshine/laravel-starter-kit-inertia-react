import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Mail, Plus, Send, Trash2, Sparkles } from 'lucide-react';

interface EmailCampaign {
    id: number;
    name: string;
    subject: string;
    status: string;
    sent_count: number;
    open_count: number;
    click_count: number;
    sent_at: string | null;
    mail_list?: { name: string };
}

interface MailList {
    id: number;
    name: string;
}

interface Props {
    campaigns: { data: EmailCampaign[] };
    mailLists: MailList[];
}

const statusVariant: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    draft: 'secondary',
    scheduled: 'outline',
    sending: 'default',
    sent: 'default',
    cancelled: 'destructive',
};

export default function EmailCampaignsIndex({ campaigns, mailLists }: Props) {
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        subject: '',
        preview_text: '',
        html_content: '',
        from_name: '',
        from_email: '',
        mail_list_id: '',
        scheduled_at: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/email-campaigns', {
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };

    const send = (id: number) => {
        if (confirm('Send this campaign now?')) {
            router.post(`/email-campaigns/${id}/send`);
        }
    };

    const destroy = (id: number) => {
        if (confirm('Delete this campaign?')) {
            router.delete(`/email-campaigns/${id}`);
        }
    };

    return (
        <AppLayout>
            <Head title="Email Campaigns" />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Email Campaigns</h1>
                        <p className="text-muted-foreground text-sm">Build, personalise, and send email campaigns with AI.</p>
                    </div>
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button data-pan="email-campaign-create-btn">
                                <Plus className="mr-2 h-4 w-4" /> New Campaign
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-lg">
                            <DialogHeader>
                                <DialogTitle>Create Email Campaign</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={submit} className="space-y-4">
                                <div>
                                    <Label>Campaign Name</Label>
                                    <Input
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="e.g. April Launch Campaign"
                                    />
                                    {errors.name && <p className="text-destructive text-xs">{errors.name}</p>}
                                </div>
                                <div>
                                    <Label>Subject Line</Label>
                                    <Input value={data.subject} onChange={(e) => setData('subject', e.target.value)} placeholder="Your subject line" />
                                    {errors.subject && <p className="text-destructive text-xs">{errors.subject}</p>}
                                </div>
                                <div>
                                    <Label>Preview Text</Label>
                                    <Input
                                        value={data.preview_text}
                                        onChange={(e) => setData('preview_text', e.target.value)}
                                        placeholder="Email preview text"
                                    />
                                </div>
                                <div>
                                    <Label>Mail List</Label>
                                    <Select value={data.mail_list_id} onValueChange={(v) => setData('mail_list_id', v)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select list" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {mailLists.map((list) => (
                                                <SelectItem key={list.id} value={String(list.id)}>
                                                    {list.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <Label>From Name</Label>
                                        <Input
                                            value={data.from_name}
                                            onChange={(e) => setData('from_name', e.target.value)}
                                            placeholder="Sender name"
                                        />
                                    </div>
                                    <div>
                                        <Label>From Email</Label>
                                        <Input
                                            type="email"
                                            value={data.from_email}
                                            onChange={(e) => setData('from_email', e.target.value)}
                                            placeholder="noreply@example.com"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <Label className="flex items-center gap-1">
                                        <Sparkles className="h-3 w-3" /> HTML Content
                                    </Label>
                                    <Textarea
                                        value={data.html_content}
                                        onChange={(e) => setData('html_content', e.target.value)}
                                        rows={4}
                                        placeholder="Campaign HTML content (AI will personalise per recipient)"
                                    />
                                </div>
                                <Button type="submit" disabled={processing} className="w-full">
                                    {processing ? 'Creating...' : 'Create Campaign'}
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div className="space-y-4">
                    {campaigns.data.map((campaign) => (
                        <Card key={campaign.id} className="group">
                            <CardContent className="flex items-center justify-between p-4">
                                <div className="flex items-center gap-3">
                                    <Mail className="h-5 w-5 text-orange-500" />
                                    <div>
                                        <p className="text-sm font-medium">{campaign.name}</p>
                                        <p className="text-muted-foreground text-xs">{campaign.subject}</p>
                                        {campaign.mail_list && (
                                            <p className="text-muted-foreground text-xs">List: {campaign.mail_list.name}</p>
                                        )}
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    {campaign.status === 'sent' && (
                                        <div className="text-muted-foreground text-right text-xs">
                                            {campaign.sent_count} sent · {campaign.open_count} opens · {campaign.click_count} clicks
                                        </div>
                                    )}
                                    <Badge variant={statusVariant[campaign.status] ?? 'secondary'}>{campaign.status}</Badge>
                                    {campaign.status === 'draft' && (
                                        <Button size="sm" onClick={() => send(campaign.id)}>
                                            <Send className="mr-1 h-3 w-3" /> Send
                                        </Button>
                                    )}
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-7 w-7 opacity-0 group-hover:opacity-100"
                                        onClick={() => destroy(campaign.id)}
                                    >
                                        <Trash2 className="h-3 w-3 text-red-500" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                    {campaigns.data.length === 0 && (
                        <div className="text-muted-foreground py-12 text-center">
                            <Mail className="mx-auto mb-2 h-8 w-8 opacity-30" />
                            <p>No email campaigns yet. Create one to get started.</p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
