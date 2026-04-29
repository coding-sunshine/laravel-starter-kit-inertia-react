import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface AlertRecord {
    id: number;
    type: string;
    title: string;
    body: string;
    severity: string;
    siding_id: number | null;
    rake_id: number | null;
    created_at: string;
}

const SEVERITY_STYLES: Record<string, string> = {
    critical: 'border-red-200 bg-red-50',
    high: 'border-orange-200 bg-orange-50',
    medium: 'border-amber-200 bg-amber-50',
    low: 'border-blue-200 bg-blue-50',
};

const SEVERITY_ORDER = ['critical', 'high', 'medium', 'low'];

export function AlertFeed({ alerts }: { alerts: Record<string, AlertRecord[]> }) {
    const allAlerts = SEVERITY_ORDER.flatMap((sev) => alerts[sev] ?? []);

    return (
        <Card className="shadow-sm">
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                    <CardTitle
                        className="text-sm font-semibold uppercase tracking-wide"
                        style={{ color: 'oklch(0.22 0.06 150)' }}
                    >
                        Alert Feed
                    </CardTitle>
                    {allAlerts.length > 0 && (
                        <Badge variant="destructive" className="text-[10px]">
                            {allAlerts.length}
                        </Badge>
                    )}
                </div>
            </CardHeader>
            <CardContent>
                {allAlerts.length === 0 ? (
                    <p className="text-sm text-gray-400">No active alerts.</p>
                ) : (
                    <div className="flex max-h-64 flex-col gap-2 overflow-y-auto pr-1">
                        {allAlerts.map((alert) => (
                            <div
                                key={alert.id}
                                className={`rounded-lg border p-3 text-sm ${SEVERITY_STYLES[alert.severity] ?? 'bg-gray-50'}`}
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <p className="font-medium leading-snug text-gray-900">{alert.title}</p>
                                    <Badge variant="outline" className="shrink-0 text-[10px] capitalize">
                                        {alert.severity}
                                    </Badge>
                                </div>
                                {alert.body && (
                                    <p className="mt-1 text-[11px] leading-relaxed text-gray-500">{alert.body}</p>
                                )}
                                <p className="mt-1 text-[10px] text-gray-400">{alert.created_at}</p>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
