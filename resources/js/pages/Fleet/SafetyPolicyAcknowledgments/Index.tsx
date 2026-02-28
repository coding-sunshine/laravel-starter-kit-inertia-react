import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { FileSignature, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Row { id: number; policy_type: string; policy_reference?: string; acknowledged_at: string; user?: { id: number; name: string }; driver?: { id: number; first_name: string; last_name: string }; }
interface Props {
    safetyPolicyAcknowledgments: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
}

export default function SafetyPolicyAcknowledgmentsIndex({ safetyPolicyAcknowledgments }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Safety policy acknowledgments', href: '/fleet/safety-policy-acknowledgments' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Safety policy acknowledgments" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Safety policy acknowledgments</h1>
                    <Button asChild><Link href="/fleet/safety-policy-acknowledgments/create"><Plus className="mr-2 size-4" />New</Link></Button>
                </div>
                {safetyPolicyAcknowledgments.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <FileSignature className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No safety policy acknowledgments yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/safety-policy-acknowledgments/create">Add acknowledgment</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Policy type</th>
                                        <th className="p-3 text-left font-medium">Reference</th>
                                        <th className="p-3 text-left font-medium">Acknowledged by</th>
                                        <th className="p-3 text-left font-medium">Acknowledged at</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {safetyPolicyAcknowledgments.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3 font-medium">{row.policy_type}</td>
                                            <td className="p-3">{row.policy_reference ?? '—'}</td>
                                            <td className="p-3">{row.user?.name ?? (row.driver ? row.driver.first_name + ' ' + row.driver.last_name : '—')}</td>
                                            <td className="p-3">{row.acknowledged_at}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {safetyPolicyAcknowledgments.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {safetyPolicyAcknowledgments.links.map((link, i) => (
                                    <Link key={i} href={link.url ?? '#'} className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}>{link.label}</Link>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
