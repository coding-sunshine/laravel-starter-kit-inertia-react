import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface SubjectOption {
    id: number;
    name: string;
    type: string;
}
interface Assessment {
    id: number;
    subject_type: string;
    subject_id: number;
    title: string;
    type: string;
    reference_number?: string;
    description?: string;
    hazards?: string;
    control_measures?: string;
    status: string;
    review_date?: string;
}
interface Props {
    riskAssessment: Assessment;
    types: { value: string; name: string }[];
    statuses: { value: string; name: string }[];
    subjectOptions: SubjectOption[];
}

export default function RiskAssessmentsEdit({
    riskAssessment,
    types,
    statuses,
    subjectOptions,
}: Props) {
    const form = useForm({
        subject_type: riskAssessment.subject_type,
        subject_id: riskAssessment.subject_id,
        title: riskAssessment.title,
        type: riskAssessment.type,
        reference_number: riskAssessment.reference_number ?? '',
        description: riskAssessment.description ?? '',
        hazards: riskAssessment.hazards ?? '',
        control_measures: riskAssessment.control_measures ?? '',
        status: riskAssessment.status,
        review_date: riskAssessment.review_date ?? '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Risk assessments', href: '/fleet/risk-assessments' },
        {
            title: 'Edit',
            href: `/fleet/risk-assessments/${riskAssessment.id}/edit`,
        },
    ];
    const subjectValue = `${form.data.subject_type}-${form.data.subject_id}`;
    const onSubjectChange = (val: string) => {
        const [type, id] = val.split('-');
        if (type && id) {
            form.setData({
                ...form.data,
                subject_type: type,
                subject_id: Number(id),
            });
        }
    };
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit risk assessment" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={`/fleet/risk-assessments/${riskAssessment.id}`}
                        >
                            Back
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        Edit risk assessment
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put(
                            `/fleet/risk-assessments/${riskAssessment.id}`,
                        );
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>Subject *</Label>
                        <select
                            required
                            value={subjectValue}
                            onChange={(e) => onSubjectChange(e.target.value)}
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {subjectOptions.map((o) => (
                                <option
                                    key={`${o.type}-${o.id}`}
                                    value={`${o.type}-${o.id}`}
                                >
                                    {o.name} ({o.type})
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Title *</Label>
                        <Input
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                            required
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Type *</Label>
                        <select
                            required
                            value={form.data.type}
                            onChange={(e) =>
                                form.setData('type', e.target.value)
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {types.map((t) => (
                                <option key={t.value} value={t.value}>
                                    {t.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Reference number</Label>
                        <Input
                            value={form.data.reference_number}
                            onChange={(e) =>
                                form.setData('reference_number', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Description</Label>
                        <textarea
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                            className="min-h-[80px] w-full rounded-md border border-input px-3 py-2 text-sm"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Hazards</Label>
                        <textarea
                            value={form.data.hazards}
                            onChange={(e) =>
                                form.setData('hazards', e.target.value)
                            }
                            className="min-h-[80px] w-full rounded-md border border-input px-3 py-2 text-sm"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Control measures</Label>
                        <textarea
                            value={form.data.control_measures}
                            onChange={(e) =>
                                form.setData('control_measures', e.target.value)
                            }
                            className="min-h-[80px] w-full rounded-md border border-input px-3 py-2 text-sm"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Status</Label>
                        <select
                            value={form.data.status}
                            onChange={(e) =>
                                form.setData('status', e.target.value)
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {statuses.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Review date</Label>
                        <Input
                            type="date"
                            value={form.data.review_date}
                            onChange={(e) =>
                                form.setData('review_date', e.target.value)
                            }
                        />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link
                                href={`/fleet/risk-assessments/${riskAssessment.id}`}
                            >
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
