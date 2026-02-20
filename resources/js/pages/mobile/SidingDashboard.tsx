import { BottomNavigation } from '@/components/mobile/BottomNavigation';
import {
    MobileCard,
    MobileFullWidthContainer,
    MobileLayout,
    MobileSafeArea,
} from '@/layouts/MobileLayout';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * SidingDashboard - Real-time operational dashboard for siding managers
 * Shows: Stock levels, pending rakes, demurrage timers, active indents
 */
export default function SidingDashboard() {
    const { props } = usePage<{ siding?: unknown; metrics?: unknown }>();
    const { siding, metrics } = props;

    const [refreshInterval] = useState(30000); // 30 seconds
    const [lastUpdated, setLastUpdated] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => {
            setLastUpdated(new Date());
            // In production, trigger data refresh via polling or WebSocket
        }, refreshInterval);

        return () => clearInterval(timer);
    }, [refreshInterval]);

    return (
        <MobileLayout>
            <MobileSafeArea>
                <MobileFullWidthContainer className="mb-20">
                    {/* Header */}
                    <div className="px-4 pt-4 pb-2">
                        <h1 className="text-2xl font-bold text-gray-900">
                            {siding?.name || 'Siding'}
                        </h1>
                        <p className="text-sm text-gray-500">
                            Updated {lastUpdated.toLocaleTimeString()}
                        </p>
                    </div>

                    {/* Stock Level Card */}
                    <MobileCard className="mx-4 mb-3">
                        <div className="mb-3 flex items-start justify-between">
                            <div>
                                <p className="text-xs tracking-wide text-gray-500 uppercase">
                                    Current Stock
                                </p>
                                <p className="text-3xl font-bold text-gray-900">
                                    {metrics?.current_stock_mt || 0} MT
                                </p>
                            </div>
                            <span
                                className={`rounded-full px-3 py-1.5 text-xs font-semibold ${
                                    (metrics?.current_stock_mt || 0) > 500
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-orange-100 text-orange-800'
                                }`}
                            >
                                {(metrics?.current_stock_mt || 0) > 500
                                    ? '✓ Adequate'
                                    : '⚠ Low'}
                            </span>
                        </div>
                        <div className="grid grid-cols-2 gap-3 border-t border-gray-200 pt-3">
                            <div>
                                <p className="text-xs text-gray-500">
                                    30-day Receipt
                                </p>
                                <p className="text-lg font-semibold text-green-600">
                                    +{metrics?.receipts_30d || 0} MT
                                </p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-500">
                                    30-day Dispatch
                                </p>
                                <p className="text-lg font-semibold text-red-600">
                                    -{metrics?.dispatches_30d || 0} MT
                                </p>
                            </div>
                        </div>
                    </MobileCard>

                    {/* Pending Rakes Card */}
                    <MobileCard className="mx-4 mb-3">
                        <div className="mb-3 flex items-start justify-between">
                            <div>
                                <p className="text-xs tracking-wide text-gray-500 uppercase">
                                    Pending Rakes
                                </p>
                                <p className="text-3xl font-bold text-gray-900">
                                    {metrics?.pending_rakes || 0}
                                </p>
                            </div>
                            <span className="rounded-full bg-blue-100 px-3 py-1.5 text-xs font-semibold text-blue-800">
                                {metrics?.rakes_in_transit || 0} In Transit
                            </span>
                        </div>
                        <div className="grid grid-cols-2 gap-3 border-t border-gray-200 pt-3">
                            <div>
                                <p className="text-xs text-gray-500">Loading</p>
                                <p className="text-lg font-semibold">
                                    {metrics?.rakes_loading || 0}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-500">Staged</p>
                                <p className="text-lg font-semibold">
                                    {metrics?.rakes_staged || 0}
                                </p>
                            </div>
                        </div>
                    </MobileCard>

                    {/* Demurrage Alert Card */}
                    <MobileCard className="mx-4 mb-3 border-l-4 border-l-red-600">
                        <div className="mb-3 flex items-start justify-between">
                            <div>
                                <p className="text-xs tracking-wide text-gray-500 uppercase">
                                    Pending Demurrage
                                </p>
                                <p className="text-3xl font-bold text-red-600">
                                    ₹
                                    {(
                                        metrics?.pending_demurrage || 0
                                    ).toLocaleString('en-IN')}
                                </p>
                            </div>
                            <span className="rounded-full bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-800">
                                {metrics?.overdue_rakes || 0} Critical
                            </span>
                        </div>
                        <div className="grid grid-cols-2 gap-3 border-t border-gray-200 pt-3">
                            <div>
                                <p className="text-xs text-gray-500">
                                    Collected
                                </p>
                                <p className="text-lg font-semibold text-green-600">
                                    ₹
                                    {(
                                        metrics?.collected_demurrage || 0
                                    ).toLocaleString('en-IN')}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-500">
                                    Monthly Avg
                                </p>
                                <p className="text-lg font-semibold">
                                    ₹
                                    {(
                                        metrics?.avg_monthly_demurrage || 0
                                    ).toLocaleString('en-IN')}
                                </p>
                            </div>
                        </div>
                    </MobileCard>

                    {/* Active Indents Card */}
                    <MobileCard className="mx-4 mb-3">
                        <div className="mb-3 flex items-start justify-between">
                            <div>
                                <p className="text-xs tracking-wide text-gray-500 uppercase">
                                    Active Indents
                                </p>
                                <p className="text-3xl font-bold text-gray-900">
                                    {metrics?.active_indents || 0}
                                </p>
                            </div>
                            <span
                                className={`rounded-full px-3 py-1.5 text-xs font-semibold ${
                                    (metrics?.overdue_indents || 0) > 0
                                        ? 'bg-red-100 text-red-800'
                                        : 'bg-green-100 text-green-800'
                                }`}
                            >
                                {(metrics?.overdue_indents || 0) > 0
                                    ? `${metrics?.overdue_indents} Overdue`
                                    : 'On Track'}
                            </span>
                        </div>
                        <div className="grid grid-cols-2 gap-3 border-t border-gray-200 pt-3">
                            <div>
                                <p className="text-xs text-gray-500">
                                    Total MT
                                </p>
                                <p className="text-lg font-semibold">
                                    {metrics?.total_indent_quantity || 0} MT
                                </p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-500">
                                    Fulfilled
                                </p>
                                <p className="text-lg font-semibold">
                                    {Math.round(
                                        (metrics?.fulfillment_rate || 0) * 100,
                                    )}
                                    %
                                </p>
                            </div>
                        </div>
                    </MobileCard>

                    {/* Key Metrics Footer */}
                    <MobileCard className="mx-4 mb-24 bg-gradient-to-r from-blue-50 to-indigo-50">
                        <div className="grid grid-cols-3 gap-3 text-center">
                            <div>
                                <p className="mb-1 text-xs text-gray-500">
                                    Avg Load Time
                                </p>
                                <p className="text-lg font-bold text-blue-600">
                                    {metrics?.avg_load_time_minutes || 0}h
                                </p>
                            </div>
                            <div>
                                <p className="mb-1 text-xs text-gray-500">
                                    On-Time Delivery
                                </p>
                                <p className="text-lg font-bold text-green-600">
                                    {metrics?.ontime_delivery_rate || 0}%
                                </p>
                            </div>
                            <div>
                                <p className="mb-1 text-xs text-gray-500">
                                    Avg Dwell Time
                                </p>
                                <p className="text-lg font-bold text-orange-600">
                                    {metrics?.avg_dwell_hours || 0}h
                                </p>
                            </div>
                        </div>
                    </MobileCard>
                </MobileFullWidthContainer>
            </MobileSafeArea>

            <BottomNavigation
                activeTab="dashboard"
                tabs={[
                    {
                        id: 'dashboard',
                        label: 'Dashboard',
                        icon: '📊',
                        href: '#',
                    },
                    { id: 'rakes', label: 'Rakes', icon: '🚂', href: '#' },
                    { id: 'stock', label: 'Stock', icon: '📦', href: '#' },
                    { id: 'menu', label: 'Menu', icon: '☰', href: '#' },
                ]}
            />
        </MobileLayout>
    );
}
