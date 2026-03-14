import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Mic, Phone, PhoneIncoming, PhoneOutgoing } from 'lucide-react';
import { useState } from 'react';

interface Contact {
    id: number;
    first_name: string;
    last_name: string | null;
}

interface CallLog {
    id: number;
    contact: Contact | null;
    call_sid: string | null;
    direction: string;
    duration_seconds: number;
    sentiment: string | null;
    outcome: string | null;
    called_at: string;
}

interface Props {
    call_logs: {
        data: CallLog[];
        current_page: number;
        last_page: number;
    };
    vapi_configured: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'AI', href: '/ai/calls' },
    { title: 'Call Logs', href: '/ai/calls' },
];

const SENTIMENT_STYLES: Record<string, string> = {
    positive: 'bg-green-100 text-green-700',
    neutral: 'bg-gray-100 text-gray-600',
    negative: 'bg-red-100 text-red-700',
};

const OUTCOME_STYLES: Record<string, string> = {
    interested: 'bg-blue-100 text-blue-700',
    'not-interested': 'bg-gray-100 text-gray-500',
    callback: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-green-100 text-green-600',
    'no-answer': 'bg-orange-100 text-orange-700',
    voicemail: 'bg-purple-100 text-purple-700',
};

function formatDuration(seconds: number): string {
    if (seconds < 60) return `${seconds}s`;
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}m ${secs}s`;
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString();
}

export default function CallLogsIndexPage({ call_logs, vapi_configured }: Props) {
    const [sentimentFilter, setSentimentFilter] = useState('');
    const [outcomeFilter, setOutcomeFilter] = useState('');

    const filtered = call_logs.data.filter((log) => {
        if (sentimentFilter && log.sentiment !== sentimentFilter) return false;
        if (outcomeFilter && log.outcome !== outcomeFilter) return false;
        return true;
    });

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Call Logs" />
            <div
                className="flex h-full flex-1 flex-col gap-6 p-6"
                data-pan="call-logs-tab"
            >
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                            <Mic className="size-5 text-blue-600" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Call Logs</h1>
                            <p className="text-sm text-gray-500">Voice powered by Vapi</p>
                        </div>
                    </div>
                    {!vapi_configured && (
                        <div className="rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs text-yellow-700">
                            Vapi API key not configured
                        </div>
                    )}
                </div>

                {/* Filters */}
                <div className="flex items-center gap-3">
                    <select
                        value={sentimentFilter}
                        onChange={(e) => setSentimentFilter(e.target.value)}
                        className="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                    >
                        <option value="">All Sentiment</option>
                        <option value="positive">Positive</option>
                        <option value="neutral">Neutral</option>
                        <option value="negative">Negative</option>
                    </select>
                    <select
                        value={outcomeFilter}
                        onChange={(e) => setOutcomeFilter(e.target.value)}
                        className="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                    >
                        <option value="">All Outcomes</option>
                        <option value="interested">Interested</option>
                        <option value="not-interested">Not Interested</option>
                        <option value="callback">Callback</option>
                        <option value="completed">Completed</option>
                        <option value="no-answer">No Answer</option>
                        <option value="voicemail">Voicemail</option>
                    </select>
                </div>

                {/* Table */}
                <div className="rounded-xl border bg-white shadow-sm overflow-hidden">
                    {filtered.length === 0 ? (
                        <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                            <div className="rounded-full bg-gray-50 p-4">
                                <Phone className="size-8 text-gray-300" />
                            </div>
                            <div>
                                <p className="font-medium text-gray-700">No call logs yet</p>
                                <p className="mt-1 text-sm text-gray-400">
                                    Call logs will appear here once Vapi calls are made.
                                </p>
                            </div>
                        </div>
                    ) : (
                        <table className="w-full">
                            <thead className="border-b bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Contact
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Direction
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Duration
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Sentiment
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Outcome
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Called At
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {filtered.map((log) => (
                                    <tr key={log.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 text-sm text-gray-900">
                                            {log.contact
                                                ? `${log.contact.first_name} ${log.contact.last_name ?? ''}`.trim()
                                                : <span className="text-gray-400">Unknown</span>
                                            }
                                        </td>
                                        <td className="px-4 py-3 text-sm">
                                            <div className="flex items-center gap-1.5 text-gray-600">
                                                {log.direction === 'outbound' ? (
                                                    <PhoneOutgoing className="size-4 text-blue-500" />
                                                ) : (
                                                    <PhoneIncoming className="size-4 text-green-500" />
                                                )}
                                                <span className="capitalize">{log.direction}</span>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-600">
                                            {formatDuration(log.duration_seconds)}
                                        </td>
                                        <td className="px-4 py-3 text-sm">
                                            {log.sentiment ? (
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                                                        SENTIMENT_STYLES[log.sentiment] ?? 'bg-gray-100 text-gray-600'
                                                    }`}
                                                >
                                                    {log.sentiment}
                                                </span>
                                            ) : (
                                                <span className="text-gray-400 text-xs">—</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-sm">
                                            {log.outcome ? (
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs font-medium capitalize ${
                                                        OUTCOME_STYLES[log.outcome] ?? 'bg-gray-100 text-gray-600'
                                                    }`}
                                                >
                                                    {log.outcome.replace('-', ' ')}
                                                </span>
                                            ) : (
                                                <span className="text-gray-400 text-xs">—</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-500">
                                            {formatDate(log.called_at)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </AppSidebarLayout>
    );
}
