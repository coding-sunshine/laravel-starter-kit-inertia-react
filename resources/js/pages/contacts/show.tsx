import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Feed } from '@/components/ui/feed';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { Stepper, type StepperStep } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    Calendar,
    CheckSquare,
    Clock,
    Edit3,
    FileText,
    Loader2,
    Mail,
    Pencil,
    Phone,
    PhoneCall,
    Plus,
    RefreshCw,
    Search,
    Sparkles,
    Tag,
    User,
    Users,
} from 'lucide-react';
import { type FormEvent, useState } from 'react';

interface ContactEmail {
    id: number;
    email: string;
    is_primary: boolean;
}

interface ContactPhone {
    id: number;
    phone: string;
    is_primary: boolean;
}

interface ContactTask {
    id: number;
    title: string;
    status: string;
    due_at: string | null;
    priority: string | null;
}

interface CallLog {
    id: number;
    direction: string;
    duration_seconds: number | null;
    outcome: string | null;
    called_at: string | null;
}

interface Activity {
    id: number;
    type: string;
    description: string;
    causer_name: string | null;
    properties: Record<string, unknown> | null;
    created_at: string | null;
}

interface SimilarContact {
    id: number;
    name: string;
    type: string;
    stage: string | null;
}

interface ContactData {
    id: number;
    first_name: string;
    last_name: string | null;
    job_title: string | null;
    type: string;
    stage: string | null;
    contact_origin: string;
    company_name: string | null;
    lead_score: number | null;
    last_contacted_at: string | null;
    next_followup_at: string | null;
    last_followup_at: string | null;
    created_at: string | null;
    extra_attributes: Record<string, unknown> | null;
    emails: ContactEmail[];
    phones: ContactPhone[];
    company: { id: number; name: string } | null;
    source: string | null;
    assigned_user: { id: number; name: string } | null;
    strategy_tags: { id: number; name: string }[];
    ai_summary: string | null;
    tasks: ContactTask[];
    call_logs: CallLog[];
    property_searches: {
        id: number;
        search_criteria: unknown;
        created_at: string | null;
    }[];
}

interface Props {
    contact: ContactData;
    activities: Activity[];
    ai_summary?: { content: string } | null;
    similar_contacts?: SimilarContact[];
}

type BadgeColor =
    | 'info'
    | 'warning'
    | 'success'
    | 'error'
    | 'primary'
    | 'secondary'
    | 'neutral';

const STAGE_CONFIG: Record<
    string,
    { label: string; color: BadgeColor; index: number }
> = {
    new_lead: { label: 'New Lead', color: 'info', index: 0 },
    contacted: { label: 'Contacted', color: 'warning', index: 1 },
    qualified: { label: 'Qualified', color: 'success', index: 2 },
    nurturing: { label: 'Nurturing', color: 'primary', index: 3 },
    hot: { label: 'Hot', color: 'error', index: 4 },
    client: { label: 'Client', color: 'success', index: 5 },
    inactive: { label: 'Inactive', color: 'neutral', index: -1 },
};

const STAGE_STEPS: StepperStep[] = [
    { id: 'new_lead', title: 'New Lead' },
    { id: 'contacted', title: 'Contacted' },
    { id: 'qualified', title: 'Qualified' },
    { id: 'nurturing', title: 'Nurturing' },
    { id: 'hot', title: 'Hot' },
    { id: 'client', title: 'Client' },
];

const TASK_STATUS_COLOR: Record<string, BadgeColor> = {
    pending: 'warning',
    in_progress: 'info',
    completed: 'success',
    cancelled: 'neutral',
};

const PRIORITY_COLOR: Record<string, BadgeColor> = {
    low: 'neutral',
    medium: 'warning',
    high: 'error',
    urgent: 'error',
};

function getActivityIcon(type: string) {
    switch (type) {
        case 'created':
            return <FileText className="h-4 w-4" />;
        case 'updated':
            return <RefreshCw className="h-4 w-4" />;
        case 'deleted':
            return <CheckSquare className="h-4 w-4" />;
        case 'called':
            return <PhoneCall className="h-4 w-4" />;
        case 'emailed':
            return <Mail className="h-4 w-4" />;
        default:
            return <Clock className="h-4 w-4" />;
    }
}

function getActivityIconClass(type: string) {
    switch (type) {
        case 'created':
            return 'border-success/30 text-success bg-success/10';
        case 'updated':
            return 'border-info/30 text-info bg-info/10';
        case 'deleted':
            return 'border-error/30 text-error bg-error/10';
        case 'called':
            return 'border-warning/30 text-warning bg-warning/10';
        case 'emailed':
            return 'border-primary/30 text-primary bg-primary/10';
        default:
            return '';
    }
}

function formatDate(date: string | null): string {
    if (!date) return '—';
    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatDateTime(date: string | null): string {
    if (!date) return '—';
    return new Date(date).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function ContactShowPage({
    contact,
    activities,
    ai_summary,
    similar_contacts,
}: Props) {
    const [editOpen, setEditOpen] = useState(false);
    const [editForm, setEditForm] = useState({
        first_name: contact.first_name,
        last_name: contact.last_name ?? '',
        job_title: contact.job_title ?? '',
        company_name: contact.company_name ?? '',
    });
    const [editSaving, setEditSaving] = useState(false);
    const [refreshingSummary, setRefreshingSummary] = useState(false);
    const [noteText, setNoteText] = useState('');
    const [noteSaving, setNoteSaving] = useState(false);

    const fullName = [contact.first_name, contact.last_name]
        .filter(Boolean)
        .join(' ');

    const initials = fullName
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'CRM', href: '/contacts' },
        { title: 'Contacts', href: '/contacts' },
        { title: fullName, href: `/contacts/${contact.id}` },
    ];

    const stageConfig = STAGE_CONFIG[contact.stage ?? ''] ?? {
        label: contact.stage ?? 'Unknown',
        color: 'neutral' as const,
        index: -1,
    };

    const currentStepIndex =
        stageConfig.index >= 0 ? stageConfig.index : -1;

    const primaryEmail = contact.emails.find((e) => e.is_primary);
    const primaryPhone = contact.phones.find((p) => p.is_primary);

    const aiSummaryContent = ai_summary?.content ?? contact.ai_summary;

    const feedItems = activities.map((activity) => ({
        id: activity.id,
        actor: activity.causer_name
            ? { name: activity.causer_name }
            : undefined,
        action: activity.description,
        timestamp: formatDateTime(activity.created_at),
        icon: getActivityIcon(activity.type),
        iconClassName: getActivityIconClass(activity.type),
    }));

    function handleEditSave(e: FormEvent) {
        e.preventDefault();
        setEditSaving(true);
        router.patch(
            `/contacts/${contact.id}`,
            editForm,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setEditOpen(false);
                    setEditSaving(false);
                },
                onError: () => setEditSaving(false),
            },
        );
    }

    function handleStageClick(stageId: string) {
        router.patch(
            `/contacts/${contact.id}/quick-edit`,
            { stage: stageId },
            { preserveScroll: true },
        );
    }

    function handleRefreshSummary() {
        setRefreshingSummary(true);
        router.post(
            `/contacts/${contact.id}/refresh-summary`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => setRefreshingSummary(false),
                onError: () => setRefreshingSummary(false),
            },
        );
    }

    function handleNoteSubmit(e: FormEvent) {
        e.preventDefault();
        if (!noteText.trim()) return;
        setNoteSaving(true);
        // Uses existing CRM notes endpoint if available
        router.post(
            `/crm-notes`,
            {
                noteable_type: 'App\\Models\\Contact',
                noteable_id: contact.id,
                content: noteText,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNoteText('');
                    setNoteSaving(false);
                },
                onError: () => setNoteSaving(false),
            },
        );
    }

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title={fullName} />
            <div
                className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6"
                data-pan="contact-detail"
            >
                {/* Back button */}
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/contacts" aria-label="Back to contacts">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                </div>

                {/* Header Card */}
                <Card>
                    <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-4">
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary/10 text-lg font-semibold text-primary ring-2 ring-primary/20">
                                {initials}
                            </div>
                            <div className="space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h1 className="text-xl font-semibold tracking-tight">
                                        {fullName}
                                    </h1>
                                    <Dialog
                                        open={editOpen}
                                        onOpenChange={setEditOpen}
                                    >
                                        <DialogTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-6 w-6"
                                            >
                                                <Pencil className="h-3 w-3" />
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>
                                                    Edit Contact
                                                </DialogTitle>
                                            </DialogHeader>
                                            <form
                                                onSubmit={handleEditSave}
                                                className="space-y-4"
                                            >
                                                <div className="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <Label htmlFor="first_name">
                                                            First Name
                                                        </Label>
                                                        <Input
                                                            id="first_name"
                                                            value={
                                                                editForm.first_name
                                                            }
                                                            onChange={(e) =>
                                                                setEditForm(
                                                                    (prev) => ({
                                                                        ...prev,
                                                                        first_name:
                                                                            e
                                                                                .target
                                                                                .value,
                                                                    }),
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                    <div>
                                                        <Label htmlFor="last_name">
                                                            Last Name
                                                        </Label>
                                                        <Input
                                                            id="last_name"
                                                            value={
                                                                editForm.last_name
                                                            }
                                                            onChange={(e) =>
                                                                setEditForm(
                                                                    (prev) => ({
                                                                        ...prev,
                                                                        last_name:
                                                                            e
                                                                                .target
                                                                                .value,
                                                                    }),
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                                <div>
                                                    <Label htmlFor="job_title">
                                                        Job Title
                                                    </Label>
                                                    <Input
                                                        id="job_title"
                                                        value={
                                                            editForm.job_title
                                                        }
                                                        onChange={(e) =>
                                                            setEditForm(
                                                                (prev) => ({
                                                                    ...prev,
                                                                    job_title:
                                                                        e.target
                                                                            .value,
                                                                }),
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div>
                                                    <Label htmlFor="company_name">
                                                        Company
                                                    </Label>
                                                    <Input
                                                        id="company_name"
                                                        value={
                                                            editForm.company_name
                                                        }
                                                        onChange={(e) =>
                                                            setEditForm(
                                                                (prev) => ({
                                                                    ...prev,
                                                                    company_name:
                                                                        e.target
                                                                            .value,
                                                                }),
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        onClick={() =>
                                                            setEditOpen(false)
                                                        }
                                                    >
                                                        Cancel
                                                    </Button>
                                                    <Button
                                                        type="submit"
                                                        disabled={editSaving}
                                                    >
                                                        {editSaving && (
                                                            <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                                                        )}
                                                        Save
                                                    </Button>
                                                </div>
                                            </form>
                                        </DialogContent>
                                    </Dialog>
                                    <Badge
                                        variant="filled"
                                        color={stageConfig.color}
                                    >
                                        {stageConfig.label}
                                    </Badge>
                                    <Badge variant="outline" color="neutral">
                                        {contact.type}
                                    </Badge>
                                </div>
                                {contact.job_title && (
                                    <p className="text-sm text-muted-foreground">
                                        {contact.job_title}
                                        {contact.company_name &&
                                            ` at ${contact.company_name}`}
                                    </p>
                                )}
                                {contact.lead_score !== null && (
                                    <div className="flex items-center gap-2">
                                        <span className="text-xs text-muted-foreground">
                                            Lead Score
                                        </span>
                                        <Progress
                                            value={contact.lead_score}
                                            className="h-1.5 w-24"
                                        />
                                        <span className="text-xs font-medium">
                                            {contact.lead_score}%
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {primaryEmail && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    asChild
                                >
                                    <a
                                        href={`mailto:${primaryEmail.email}`}
                                    >
                                        <Mail className="mr-1.5 h-3.5 w-3.5" />
                                        Email
                                    </a>
                                </Button>
                            )}
                            {primaryPhone && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    asChild
                                >
                                    <a
                                        href={`tel:${primaryPhone.phone}`}
                                    >
                                        <Phone className="mr-1.5 h-3.5 w-3.5" />
                                        Call
                                    </a>
                                </Button>
                            )}
                            <Button variant="outline" size="sm">
                                <FileText className="mr-1.5 h-3.5 w-3.5" />
                                Note
                            </Button>
                            <Button variant="outline" size="sm">
                                <CheckSquare className="mr-1.5 h-3.5 w-3.5" />
                                Task
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* 2-column layout */}
                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Main Column */}
                    <div className="flex flex-col gap-6 lg:col-span-2">
                        {/* AI Summary */}
                        <Card className="border-l-4 border-l-amber-500">
                            <CardHeader>
                                <CardTitle className="flex items-center justify-between text-base">
                                    <span className="flex items-center gap-2">
                                        <Sparkles className="h-4 w-4 text-amber-500" />
                                        AI Summary
                                    </span>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={handleRefreshSummary}
                                        disabled={refreshingSummary}
                                    >
                                        {refreshingSummary ? (
                                            <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                        ) : (
                                            <RefreshCw className="h-3.5 w-3.5" />
                                        )}
                                        <span className="ml-1.5">
                                            Refresh
                                        </span>
                                    </Button>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {aiSummaryContent ? (
                                    <p className="text-sm leading-relaxed text-muted-foreground">
                                        {aiSummaryContent}
                                    </p>
                                ) : (
                                    <div className="space-y-2">
                                        <Skeleton className="h-4 w-full" />
                                        <Skeleton className="h-4 w-3/4" />
                                        <Skeleton className="h-4 w-1/2" />
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Inline Note Form */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Edit3 className="h-4 w-4" />
                                    Quick Note
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={handleNoteSubmit}
                                    className="flex gap-2"
                                >
                                    <Textarea
                                        placeholder="Add a note..."
                                        value={noteText}
                                        onChange={(e) =>
                                            setNoteText(e.target.value)
                                        }
                                        className="min-h-[60px] flex-1 resize-none"
                                    />
                                    <Button
                                        type="submit"
                                        size="sm"
                                        disabled={
                                            noteSaving || !noteText.trim()
                                        }
                                        className="self-end"
                                    >
                                        {noteSaving ? (
                                            <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                        ) : (
                                            <Plus className="h-3.5 w-3.5" />
                                        )}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        {/* Activity Timeline */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Activity Timeline
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {feedItems.length > 0 ? (
                                    <Feed items={feedItems} />
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No activity recorded yet.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div className="flex flex-col gap-6">
                        {/* Stage Progression — Interactive */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Stage Progression
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    {STAGE_STEPS.map((step, idx) => {
                                        const isActive =
                                            idx === currentStepIndex;
                                        const isCompleted =
                                            idx < currentStepIndex;
                                        return (
                                            <button
                                                key={step.id}
                                                type="button"
                                                onClick={() =>
                                                    handleStageClick(step.id)
                                                }
                                                className={`flex w-full items-center gap-3 rounded-lg border px-3 py-2 text-left text-sm transition-colors hover:bg-accent ${
                                                    isActive
                                                        ? 'border-primary bg-primary/5 font-medium text-primary'
                                                        : isCompleted
                                                          ? 'border-success/30 text-success'
                                                          : 'border-transparent text-muted-foreground'
                                                }`}
                                            >
                                                <span
                                                    className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs ${
                                                        isActive
                                                            ? 'bg-primary text-primary-foreground'
                                                            : isCompleted
                                                              ? 'bg-success/20 text-success'
                                                              : 'bg-muted text-muted-foreground'
                                                    }`}
                                                >
                                                    {isCompleted
                                                        ? '✓'
                                                        : idx + 1}
                                                </span>
                                                {step.title}
                                            </button>
                                        );
                                    })}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Similar Contacts (AI) */}
                        {similar_contacts &&
                            similar_contacts.length > 0 && (
                                <Card className="border-l-4 border-l-purple-500">
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2 text-base">
                                            <Users className="h-4 w-4 text-purple-500" />
                                            Similar Contacts
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ul className="space-y-2">
                                            {similar_contacts.map((sc) => (
                                                <li key={sc.id}>
                                                    <Link
                                                        href={`/contacts/${sc.id}`}
                                                        className="flex items-center justify-between rounded-lg border px-3 py-2 text-sm hover:bg-accent"
                                                    >
                                                        <span className="font-medium">
                                                            {sc.name}
                                                        </span>
                                                        <div className="flex gap-1">
                                                            <Badge
                                                                variant="outline"
                                                                color="neutral"
                                                                className="text-[10px]"
                                                            >
                                                                {sc.type}
                                                            </Badge>
                                                            {sc.stage && (
                                                                <Badge
                                                                    variant="soft"
                                                                    color={
                                                                        STAGE_CONFIG[
                                                                            sc
                                                                                .stage
                                                                        ]
                                                                            ?.color ??
                                                                        'neutral'
                                                                    }
                                                                    className="text-[10px]"
                                                                >
                                                                    {STAGE_CONFIG[
                                                                        sc
                                                                            .stage
                                                                    ]?.label ??
                                                                        sc.stage}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    </CardContent>
                                </Card>
                            )}

                        {/* Contact Info */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Contact Info
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* Emails */}
                                {contact.emails.length > 0 && (
                                    <div className="space-y-2">
                                        {contact.emails.map((email) => (
                                            <dl
                                                key={email.id}
                                                className="space-y-0.5"
                                            >
                                                <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                                    <Mail className="h-3.5 w-3.5" />
                                                    Email
                                                    {email.is_primary && (
                                                        <Badge
                                                            variant="soft"
                                                            color="info"
                                                            className="px-1 py-0 text-[10px]"
                                                        >
                                                            Primary
                                                        </Badge>
                                                    )}
                                                </dt>
                                                <dd className="text-sm">
                                                    <a
                                                        href={`mailto:${email.email}`}
                                                        className="text-primary hover:underline"
                                                    >
                                                        {email.email}
                                                    </a>
                                                </dd>
                                            </dl>
                                        ))}
                                    </div>
                                )}

                                {/* Phones */}
                                {contact.phones.length > 0 && (
                                    <div className="space-y-2">
                                        {contact.phones.map((phone) => (
                                            <dl
                                                key={phone.id}
                                                className="space-y-0.5"
                                            >
                                                <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                                    <Phone className="h-3.5 w-3.5" />
                                                    Phone
                                                    {phone.is_primary && (
                                                        <Badge
                                                            variant="soft"
                                                            color="info"
                                                            className="px-1 py-0 text-[10px]"
                                                        >
                                                            Primary
                                                        </Badge>
                                                    )}
                                                </dt>
                                                <dd className="text-sm">
                                                    <a
                                                        href={`tel:${phone.phone}`}
                                                        className="text-primary hover:underline"
                                                    >
                                                        {phone.phone}
                                                    </a>
                                                </dd>
                                            </dl>
                                        ))}
                                    </div>
                                )}

                                <Separator />

                                {/* Company */}
                                <dl className="space-y-0.5">
                                    <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                        <Building2 className="h-3.5 w-3.5" />
                                        Company
                                    </dt>
                                    <dd className="text-sm">
                                        {contact.company?.name ??
                                            contact.company_name ??
                                            '—'}
                                    </dd>
                                </dl>

                                {/* Source */}
                                <dl className="space-y-0.5">
                                    <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                        <Tag className="h-3.5 w-3.5" />
                                        Source
                                    </dt>
                                    <dd className="text-sm">
                                        {contact.source ?? '—'}
                                    </dd>
                                </dl>

                                {/* Origin */}
                                <dl className="space-y-0.5">
                                    <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                        <Search className="h-3.5 w-3.5" />
                                        Origin
                                    </dt>
                                    <dd className="text-sm">
                                        {contact.contact_origin}
                                    </dd>
                                </dl>

                                {/* Assigned User */}
                                <dl className="space-y-0.5">
                                    <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                        <User className="h-3.5 w-3.5" />
                                        Assigned To
                                    </dt>
                                    <dd className="text-sm">
                                        {contact.assigned_user?.name ?? '—'}
                                    </dd>
                                </dl>

                                {/* Dates */}
                                <Separator />

                                <dl className="space-y-0.5">
                                    <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                        <Calendar className="h-3.5 w-3.5" />
                                        Last Contacted
                                    </dt>
                                    <dd className="text-sm">
                                        {formatDate(
                                            contact.last_contacted_at,
                                        )}
                                    </dd>
                                </dl>

                                <dl className="space-y-0.5">
                                    <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                        <Clock className="h-3.5 w-3.5" />
                                        Next Follow-up
                                    </dt>
                                    <dd className="text-sm">
                                        {formatDate(
                                            contact.next_followup_at,
                                        )}
                                    </dd>
                                </dl>

                                {/* Strategy Tags */}
                                {contact.strategy_tags.length > 0 && (
                                    <>
                                        <Separator />
                                        <dl className="space-y-1">
                                            <dt className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                                <Tag className="h-3.5 w-3.5" />
                                                Strategy Tags
                                            </dt>
                                            <dd className="flex flex-wrap gap-1">
                                                {contact.strategy_tags.map(
                                                    (tag) => (
                                                        <Badge
                                                            key={tag.id}
                                                            variant="soft"
                                                            color="secondary"
                                                        >
                                                            {tag.name}
                                                        </Badge>
                                                    ),
                                                )}
                                            </dd>
                                        </dl>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Upcoming Tasks */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <CheckSquare className="h-4 w-4" />
                                    Tasks
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {contact.tasks.length > 0 ? (
                                    <ul className="space-y-3">
                                        {contact.tasks.map((task) => (
                                            <li
                                                key={task.id}
                                                className="flex items-start justify-between gap-2"
                                            >
                                                <div className="min-w-0 flex-1 space-y-0.5">
                                                    <p className="truncate text-sm font-medium">
                                                        {task.title}
                                                    </p>
                                                    {task.due_at && (
                                                        <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                                            <Calendar className="h-3 w-3" />
                                                            {formatDate(
                                                                task.due_at,
                                                            )}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="flex shrink-0 items-center gap-1">
                                                    {task.priority && (
                                                        <Badge
                                                            variant="soft"
                                                            color={
                                                                PRIORITY_COLOR[
                                                                    task.priority
                                                                ] ?? 'neutral'
                                                            }
                                                            className="text-[10px]"
                                                        >
                                                            {task.priority}
                                                        </Badge>
                                                    )}
                                                    <Badge
                                                        variant="outline"
                                                        color={
                                                            TASK_STATUS_COLOR[
                                                                task.status
                                                            ] ?? 'neutral'
                                                        }
                                                        className="text-[10px]"
                                                    >
                                                        {task.status}
                                                    </Badge>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No tasks assigned.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Property Interests */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Search className="h-4 w-4" />
                                    Property Interests
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {contact.property_searches.length > 0 ? (
                                    <ul className="space-y-3">
                                        {contact.property_searches.map(
                                            (search) => (
                                                <li
                                                    key={search.id}
                                                    className="rounded-lg border bg-muted/50 p-3"
                                                >
                                                    <p className="text-xs text-muted-foreground">
                                                        <Calendar className="mr-1 inline h-3 w-3" />
                                                        {formatDate(
                                                            search.created_at,
                                                        )}
                                                    </p>
                                                    <pre className="mt-1 overflow-x-auto text-xs">
                                                        {typeof search.search_criteria ===
                                                        'string'
                                                            ? search.search_criteria
                                                            : JSON.stringify(
                                                                  search.search_criteria,
                                                                  null,
                                                                  2,
                                                              )}
                                                    </pre>
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No property searches recorded.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppSidebarLayout>
    );
}
