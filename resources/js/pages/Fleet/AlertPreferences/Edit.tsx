import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface AlertPreference {
    id: number;
    alert_type: string;
    email_enabled: boolean;
    sms_enabled: boolean;
    push_enabled: boolean;
    in_app_enabled: boolean;
    escalation_minutes: number;
    weekend_enabled: boolean;
}
interface Props {
    alertPreference: AlertPreference;
}

export default function FleetAlertPreferencesEdit({ alertPreference }: Props) {
    const form = useForm({
        alert_type: alertPreference.alert_type,
        email_enabled: alertPreference.email_enabled,
        sms_enabled: alertPreference.sms_enabled,
        push_enabled: alertPreference.push_enabled,
        in_app_enabled: alertPreference.in_app_enabled,
        escalation_minutes: alertPreference.escalation_minutes,
        weekend_enabled: alertPreference.weekend_enabled,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Alert preferences', href: '/fleet/alert-preferences' },
        {
            title: 'Edit',
            href: `/fleet/alert-preferences/${alertPreference.id}/edit`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit alert preference" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/alert-preferences">Back</Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        Edit alert preference
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put(
                            `/fleet/alert-preferences/${alertPreference.id}`,
                        );
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>Alert type</Label>
                        <Input
                            value={form.data.alert_type}
                            readOnly
                            className="bg-muted"
                        />
                    </div>
                    <div className="flex flex-wrap gap-4">
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={form.data.email_enabled}
                                onChange={(e) =>
                                    form.setData(
                                        'email_enabled',
                                        e.target.checked,
                                    )
                                }
                                className="rounded border-input"
                            />
                            <span className="text-sm">Email enabled</span>
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={form.data.in_app_enabled}
                                onChange={(e) =>
                                    form.setData(
                                        'in_app_enabled',
                                        e.target.checked,
                                    )
                                }
                                className="rounded border-input"
                            />
                            <span className="text-sm">In-app enabled</span>
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={form.data.push_enabled}
                                onChange={(e) =>
                                    form.setData(
                                        'push_enabled',
                                        e.target.checked,
                                    )
                                }
                                className="rounded border-input"
                            />
                            <span className="text-sm">Push enabled</span>
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={form.data.weekend_enabled}
                                onChange={(e) =>
                                    form.setData(
                                        'weekend_enabled',
                                        e.target.checked,
                                    )
                                }
                                className="rounded border-input"
                            />
                            <span className="text-sm">Weekend enabled</span>
                        </label>
                    </div>
                    <div className="space-y-2">
                        <Label>Escalation (minutes)</Label>
                        <Input
                            type="number"
                            min={0}
                            value={form.data.escalation_minutes}
                            onChange={(e) =>
                                form.setData(
                                    'escalation_minutes',
                                    Number(e.target.value),
                                )
                            }
                        />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Update
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/fleet/alert-preferences">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
