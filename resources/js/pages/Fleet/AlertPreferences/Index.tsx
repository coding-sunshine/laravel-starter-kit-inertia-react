import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Bell, Pencil } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Row { id: number; alert_type: string; email_enabled: boolean; in_app_enabled: boolean; }
interface Props { alertPreferences: Row[]; }

export default function FleetAlertPreferencesIndex({ alertPreferences }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Alert preferences', href: '/fleet/alert-preferences' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Alert preferences" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Alert preferences</h1>
                <p className="text-muted-foreground text-sm">Manage how you receive alerts for this organization.</p>
                {alertPreferences.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Bell className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No alert preferences set yet.</p>
                    </div>
                ) : (
                    <div className="rounded-md border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="p-3 text-left font-medium">Alert type</th>
                                    <th className="p-3 text-left font-medium">Email</th>
                                    <th className="p-3 text-left font-medium">In-app</th>
                                    <th className="p-3 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {alertPreferences.map((row) => (
                                    <tr key={row.id} className="border-b last:border-0">
                                        <td className="p-3">{row.alert_type}</td>
                                        <td className="p-3">{row.email_enabled ? 'Yes' : 'No'}</td>
                                        <td className="p-3">{row.in_app_enabled ? 'Yes' : 'No'}</td>
                                        <td className="p-3 text-right">
                                            <Button variant="outline" size="sm" asChild><Link href={`/fleet/alert-preferences/${row.id}/edit`}><Pencil className="mr-1 size-3.5" />Edit</Link></Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
