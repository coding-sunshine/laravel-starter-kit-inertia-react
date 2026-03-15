import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { AlertCircle, CheckCircle, FileText, RefreshCw, Users, XCircle } from 'lucide-react';

interface XeroConnection {
    id: number;
    xero_tenant_id: string;
    xero_tenant_name: string | null;
    connected_at: string | null;
    disconnected_at: string | null;
}

interface XeroReconciliation {
    id: number;
    xero_payment_id: string;
    amount: number;
    payment_date: string | null;
    reconciled_at: string | null;
    xero_invoice: {
        id: number;
        xero_invoice_id: string;
        invoice_number: string | null;
    } | null;
}

interface Props {
    connection: XeroConnection | null;
    xero_contacts_count: number;
    xero_invoices_count: number;
    reconciliations_count: number;
    recent_reconciliations: XeroReconciliation[];
    is_configured: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Xero Integration', href: '/xero' },
];

function formatDate(dateString: string | null): string {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('en-AU', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-AU', { style: 'currency', currency: 'AUD' }).format(amount);
}

export default function XeroIndex({
    connection,
    xero_contacts_count,
    xero_invoices_count,
    reconciliations_count,
    recent_reconciliations,
    is_configured,
}: Props) {
    const isConnected = !!connection;

    function handleConnect() {
        router.visit('/xero/connect');
    }

    function handleDisconnect() {
        router.post('/xero/disconnect');
    }

    function handleSyncContacts() {
        router.post('/xero/sync-contacts');
    }

    function handleSyncInvoices() {
        router.post('/xero/sync-invoices');
    }

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Xero Integration" />

            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Xero Integration</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Connect your Xero account to sync contacts and invoices automatically.
                    </p>
                </div>

                {/* Connection status card */}
                <div
                    className={`rounded-lg border p-5 ${
                        !is_configured
                            ? 'border-yellow-200 bg-yellow-50'
                            : isConnected
                              ? 'border-green-200 bg-green-50'
                              : 'border-red-200 bg-red-50'
                    }`}
                >
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            {!is_configured ? (
                                <AlertCircle className="h-6 w-6 text-yellow-500" />
                            ) : isConnected ? (
                                <CheckCircle className="h-6 w-6 text-green-500" />
                            ) : (
                                <XCircle className="h-6 w-6 text-red-500" />
                            )}
                            <div>
                                <p className="font-semibold text-gray-900">
                                    {!is_configured
                                        ? 'Not Configured'
                                        : isConnected
                                          ? `Connected to ${connection.xero_tenant_name ?? 'Xero'}`
                                          : 'Not Connected'}
                                </p>
                                <p className="text-sm text-gray-600">
                                    {!is_configured
                                        ? 'XERO_CLIENT_ID and XERO_CLIENT_SECRET are not set. OAuth is deferred.'
                                        : isConnected
                                          ? `Connected since ${formatDate(connection.connected_at)}`
                                          : 'Connect your Xero account to start syncing.'}
                                </p>
                            </div>
                        </div>

                        <div className="flex gap-2">
                            {isConnected ? (
                                <button
                                    onClick={handleDisconnect}
                                    data-pan="xero-tab"
                                    className="rounded-md bg-white px-4 py-2 text-sm font-medium text-red-600 shadow-sm ring-1 ring-red-200 hover:bg-red-50"
                                >
                                    Disconnect
                                </button>
                            ) : (
                                <button
                                    onClick={handleConnect}
                                    data-pan="xero-tab"
                                    disabled={!is_configured}
                                    className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {is_configured ? 'Connect to Xero' : 'OAuth Deferred'}
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Stats row */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div className="rounded-lg border bg-white p-5 shadow-sm">
                        <div className="flex items-center gap-3">
                            <Users className="h-8 w-8 text-blue-500" />
                            <div>
                                <p className="text-2xl font-bold text-gray-900">{xero_contacts_count}</p>
                                <p className="text-sm text-gray-500">Synced Contacts</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg border bg-white p-5 shadow-sm">
                        <div className="flex items-center gap-3">
                            <FileText className="h-8 w-8 text-indigo-500" />
                            <div>
                                <p className="text-2xl font-bold text-gray-900">{xero_invoices_count}</p>
                                <p className="text-sm text-gray-500">Synced Invoices</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg border bg-white p-5 shadow-sm">
                        <div className="flex items-center gap-3">
                            <CheckCircle className="h-8 w-8 text-green-500" />
                            <div>
                                <p className="text-2xl font-bold text-gray-900">{reconciliations_count}</p>
                                <p className="text-sm text-gray-500">Reconciliations</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Action buttons */}
                <div className="flex gap-3">
                    <button
                        onClick={handleSyncContacts}
                        disabled={!isConnected}
                        data-pan="xero-sync-contacts"
                        className="flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <RefreshCw className="h-4 w-4" />
                        Sync Contacts
                    </button>

                    <button
                        onClick={handleSyncInvoices}
                        disabled={!isConnected}
                        data-pan="xero-sync-invoices"
                        className="flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <RefreshCw className="h-4 w-4" />
                        Sync Invoices
                    </button>
                </div>

                {/* Recent reconciliations */}
                <div className="rounded-lg border bg-white shadow-sm">
                    <div className="border-b px-5 py-4">
                        <h2 className="font-semibold text-gray-900">Recent Reconciliations</h2>
                    </div>

                    {recent_reconciliations.length === 0 ? (
                        <div className="px-5 py-8 text-center text-sm text-gray-400">No reconciliations yet.</div>
                    ) : (
                        <div className="divide-y">
                            {recent_reconciliations.map((rec) => (
                                <div key={rec.id} className="flex items-center justify-between px-5 py-3">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">
                                            Payment {rec.xero_payment_id}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            Invoice:{' '}
                                            {rec.xero_invoice?.invoice_number ?? rec.xero_invoice?.xero_invoice_id ?? '—'}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-semibold text-gray-900">{formatCurrency(rec.amount)}</p>
                                        <p className="text-xs text-gray-400">{formatDate(rec.payment_date)}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppSidebarLayout>
    );
}
