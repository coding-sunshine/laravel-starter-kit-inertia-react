import { DEFAULT_LIVE_RAKE_WORKFLOW_STEPS, formatRakeSequenceBySiding, SectionHeader, SIDING_ACCENT } from '../dashboard';
import type {
    CoalTransportReportData,
    DailyRakeDetailsData,
    DashboardFilters,
    LiveRakeStatusRow,
    ShiftWiseVehicleReceiptPoint,
    SidingOption,
    TruckReceiptHour,
} from './types';
import { Button } from '@/components/ui/button';
import { RakeWorkflowProgressCell } from '@/components/rake-workflow-progress';
import { ArrowUp, BarChart3, Calendar, FileSpreadsheet, Train, Truck } from 'lucide-react';
import { Fragment } from 'react';
import {
    Bar,
    BarChart as RechartsBarChart,
    CartesianGrid,
    Legend,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface Props {
    canWidget: (name: string) => boolean;
    coalTransportReport: CoalTransportReportData | undefined;
    canExportCoalTransport: boolean;
    dailyRakeDetails: DailyRakeDetailsData | undefined;
    truckReceiptTrend: TruckReceiptHour[];
    shiftWiseVehicleReceipt: ShiftWiseVehicleReceiptPoint[];
    liveRakeStatus: LiveRakeStatusRow[];
    filters: DashboardFilters;
    applyCoalTransportDate: (date: string) => void;
    applyDailyRakeDate: (date: string) => void;
}

export function Operations({
    canWidget,
    coalTransportReport,
    canExportCoalTransport,
    dailyRakeDetails,
    truckReceiptTrend,
    shiftWiseVehicleReceipt,
    liveRakeStatus,
    filters,
    applyCoalTransportDate,
    applyDailyRakeDate,
}: Props) {
    return (
        <>
            <div className="space-y-6">
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {canWidget('dashboard.widgets.operations_coal_transport') ? (
                    <div className="dashboard-card min-w-0 rounded-xl border-0 p-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <SectionHeader icon={Truck} title="Coal Transport Report" subtitle="Trips and quantity by shift and siding" titleClassName="font-bold text-black" />
                            <div className="flex flex-wrap items-center gap-2">
                                <label htmlFor="coal-transport-date" className="text-xs font-medium text-gray-600">Date</label>
                                <input
                                    id="coal-transport-date"
                                    type="date"
                                    value={filters.coal_transport_date ?? coalTransportReport?.date ?? ''}
                                    onChange={(e) => {
                                        const v = e.target.value;
                                        applyCoalTransportDate(v ?? '');
                                    }}
                                    className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
                                />
                                {(() => {
                                    const coalDate =
                                        filters.coal_transport_date ?? coalTransportReport?.date ?? '';
                                    if (coalDate && canExportCoalTransport) {
                                        return (
                                            <Button variant="outline" size="sm" asChild>
                                                <a
                                                    href={`/exports/coal-transport-report?date=${encodeURIComponent(coalDate)}`}
                                                    data-pan="dashboard-coal-transport-export-xlsx"
                                                >
                                                    <FileSpreadsheet className="mr-1.5 h-4 w-4" />
                                                    Export XLSX
                                                </a>
                                            </Button>
                                        );
                                    }
                                    return (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled
                                            title={
                                                !coalDate
                                                    ? 'Select a date'
                                                    : 'You do not have permission to export this report'
                                            }
                                        >
                                            <FileSpreadsheet className="mr-1.5 h-4 w-4" />
                                            Export XLSX
                                        </Button>
                                    );
                                })()}
                            </div>
                        </div>
                        {coalTransportReport ? (
                            <>
                                <p className="mt-1 text-xs text-gray-600">
                                    {new Date(coalTransportReport.date + 'T00:00:00').toLocaleDateString('en-IN', { day: '2-digit', month: '2-digit', year: 'numeric' })}
                                </p>
                                <div className="mt-3 overflow-hidden rounded-xl border border-[#d5dbe4] bg-white">
                                    <div className="dashboard-table-scroll max-h-[420px] overflow-y-auto overflow-x-auto">
                                        <table className="w-full border-separate text-xs" style={{ borderSpacing: 0 }}>
                                            <thead className="sticky top-0 z-10 bg-[#eef2f7] text-black">
                                                <tr>
                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">Sl No</th>
                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">Shift</th>
                                                    {coalTransportReport.sidings.map((s) => (
                                                        <th key={s.id} colSpan={2} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                            {s.name}
                                                        </th>
                                                    ))}
                                                    <th colSpan={2} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                        Total
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <th className="border border-[#d5dbe4] px-3 py-2" />
                                                    <th className="border border-[#d5dbe4] px-3 py-2" />
                                                    {coalTransportReport.sidings.flatMap((s) => [
                                                        <th key={`${s.id}-t`} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                            Trips
                                                        </th>,
                                                        <th key={`${s.id}-q`} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                            Qty
                                                        </th>,
                                                    ])}
                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">Trips</th>
                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {coalTransportReport.rows.map((row) => (
                                                    <tr key={row.shift_label} className="bg-white">
                                                        <td className="border border-[#d5dbe4] px-3 py-2 tabular-nums">{row.sl_no}</td>
                                                        <td className="border border-[#d5dbe4] px-3 py-2 font-medium">{row.shift_label}</td>
                                                        {row.siding_metrics.map((m) => (
                                                            <Fragment key={m.siding_name}>
                                                                <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{m.trips}</td>
                                                                <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                                    {m.qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                                </td>
                                                            </Fragment>
                                                        ))}
                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{row.total_trips}</td>
                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                            {row.total_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                        </td>
                                                    </tr>
                                                ))}
                                                <tr className="bg-[#f1f5f9] font-semibold text-black">
                                                    <td className="border border-[#d5dbe4] px-3 py-2" />
                                                    <td className="border border-[#d5dbe4] px-3 py-2">TOTAL</td>
                                                    {coalTransportReport.totals.siding_metrics.map((m) => (
                                                        <Fragment key={m.siding_name}>
                                                            <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{m.trips}</td>
                                                            <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                                {m.qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                            </td>
                                                        </Fragment>
                                                    ))}
                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{coalTransportReport.totals.total_trips}</td>
                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                        {coalTransportReport.totals.total_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No coal transport data.</div>
                        )}
                    </div>
                    ) : null}
                    {canWidget('dashboard.widgets.operations_daily_rake_details') ? (
                    <div className="dashboard-card rounded-xl border-0 p-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <SectionHeader icon={Calendar} title="Daily rake details" subtitle="One day — filtered by selected sidings" titleClassName="font-bold text-black" />
                            <div className="flex items-center gap-2">
                                <label htmlFor="daily-rake-date" className="text-xs font-medium text-gray-600">Date</label>
                                <input
                                    id="daily-rake-date"
                                    type="date"
                                    value={filters.daily_rake_date ?? dailyRakeDetails?.date ?? ''}
                                    onChange={(e) => {
                                        const v = e.target.value;
                                        applyDailyRakeDate(v ?? '');
                                    }}
                                    className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
                                />
                            </div>
                        </div>
                        {dailyRakeDetails ? (
                            <>
                                <p className="mt-1 text-xs text-gray-600">
                                    {new Date(dailyRakeDetails.date + 'T00:00:00').toLocaleDateString('en-IN', { day: '2-digit', month: '2-digit', year: 'numeric' })}
                                    {dailyRakeDetails.rows.length > 0 && (
                                        <span className="ml-2 text-gray-500">
                                            ({dailyRakeDetails.rows[0].year})
                                        </span>
                                    )}
                                    {dailyRakeDetails.totals.day_rakes === 0 && dailyRakeDetails.rows.length > 0 && (
                                        <span className="ml-2 text-amber-600">— No rake dispatches for this date</span>
                                    )}
                                </p>
                                <div className="mt-3 overflow-hidden rounded-xl border border-[#d5dbe4] bg-white">
                                    <div className="dashboard-table-scroll max-h-[420px] overflow-y-auto overflow-x-auto">
                                        <table className="w-full border-separate text-xs" style={{ borderSpacing: 0 }}>
                                            <thead className="sticky top-0 z-10 bg-[#eef2f7] text-black">
                                                <tr>
                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">SL NO</th>
                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">SIDING</th>
                                                    <th colSpan={2} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                        DAY
                                                    </th>
                                                    <th colSpan={2} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                        MONTH
                                                    </th>
                                                    <th colSpan={2} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                        YEAR
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <th className="border border-[#d5dbe4] px-3 py-2" />
                                                    <th className="border border-[#d5dbe4] px-3 py-2" />
                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">RAKES</th>
                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">QTY</th>
                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">RAKES</th>
                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">QTY</th>
                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">RAKES</th>
                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">QTY</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {dailyRakeDetails.rows.map((r) => (
                                                    <tr key={r.siding_name} className="bg-white">
                                                        <td className="border border-[#d5dbe4] px-3 py-2 tabular-nums">{r.sl_no}</td>
                                                        <td className="border border-[#d5dbe4] px-3 py-2 font-medium">{r.siding_name}</td>
                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{r.day_rakes}</td>
                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                            {r.day_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                        </td>
                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{r.month_rakes}</td>
                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                            {r.month_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                        </td>
                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{r.year_rakes}</td>
                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                            {r.year_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                        </td>
                                                    </tr>
                                                ))}
                                                <tr className="bg-[#f1f5f9] font-semibold text-black">
                                                    <td className="border border-[#d5dbe4] px-3 py-2" />
                                                    <td className="border border-[#d5dbe4] px-3 py-2">TOTAL</td>
                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{dailyRakeDetails.totals.day_rakes}</td>
                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                        {dailyRakeDetails.totals.day_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{dailyRakeDetails.totals.month_rakes}</td>
                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                        {dailyRakeDetails.totals.month_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{dailyRakeDetails.totals.year_rakes}</td>
                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                        {dailyRakeDetails.totals.year_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No siding selected or no data for this date.</div>
                        )}
                    </div>
                    ) : null}
                </div>
            </div>

            <div className="space-y-6">
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {canWidget('dashboard.widgets.operations_truck_receipt_trend') ? (
                    <div className="dashboard-card rounded-xl border-0 p-5">
                        <SectionHeader icon={BarChart3} title="Truck receipt trend" subtitle="Vehicles arrived per hour (today)" />
                        {truckReceiptTrend.length > 0 ? (
                            <ResponsiveContainer width="100%" height={260}>
                                <RechartsBarChart data={truckReceiptTrend} margin={{ top: 8, right: 8, left: 8, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                    <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <Tooltip formatter={(v: number | undefined) => `Vehicles: ${v ?? 0}`} />
                                    <Bar dataKey="count" fill="#3B82F6" radius={[4, 4, 0, 0]} barSize={24} isAnimationActive />
                                </RechartsBarChart>
                            </ResponsiveContainer>
                        ) : (
                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No vehicle arrivals for today.</div>
                        )}
                    </div>
                    ) : null}
                    {canWidget('dashboard.widgets.operations_shift_vehicle_receipt') ? (
                    <div className="dashboard-card rounded-xl border-0 p-5">
                        <SectionHeader icon={BarChart3} title="Shift-wise vehicle receipt" subtitle="Vehicles received by shift and siding (today)" />
                        {shiftWiseVehicleReceipt.length > 0 ? (() => {
                            const sidingKeys = Object.keys(shiftWiseVehicleReceipt[0]).filter((k) => k !== 'shift_label');
                            return (
                                <ResponsiveContainer width="100%" height={260}>
                                    <RechartsBarChart data={shiftWiseVehicleReceipt} margin={{ top: 8, right: 8, left: 8, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                        <XAxis dataKey="shift_label" tick={{ fontSize: 11 }} />
                                        <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                        <Tooltip formatter={(v: number | undefined) => `Vehicles: ${v ?? 0}`} />
                                        <Legend />
                                        {sidingKeys.map((sidingName, i) => (
                                            <Bar
                                                key={sidingName}
                                                dataKey={sidingName}
                                                name={sidingName}
                                                fill={SIDING_ACCENT[sidingName] ?? ['#3B82F6', '#10B981', '#F59E0B'][i % 3]}
                                                radius={[4, 4, 0, 0]}
                                                barSize={24}
                                                isAnimationActive
                                            />
                                        ))}
                                    </RechartsBarChart>
                                </ResponsiveContainer>
                            );
                        })() : (
                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No shift-wise vehicle data for today.</div>
                        )}
                    </div>
                    ) : null}
                </div>
                {canWidget('dashboard.widgets.operations_live_rake_status') ? (
                 <div className="dashboard-card rounded-xl border-0 p-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <SectionHeader icon={Train} title="Live rake status" subtitle="Pending on siding — no weighment receipt yet" />
                        </div>
                        <p className="mt-1 text-xs text-gray-600">
                            Last updated: Just now
                            {liveRakeStatus.length > 0 && (
                                <span className="ml-2 font-medium text-gray-600">
                                    • {liveRakeStatus.length} active rake{liveRakeStatus.length === 1 ? '' : 's'}
                                </span>
                            )}
                        </p>
                        {liveRakeStatus.length > 0 ? (
                            <div className="dashboard-table-scroll mt-4 max-h-[520px] overflow-y-auto overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="sticky top-0 z-10 bg-white shadow-sm">
                                        <tr className="border-b text-left text-gray-600">
                                            <th className="group cursor-pointer pb-3 pl-4 pr-2 pt-2 font-medium">
                                                <span className="inline-flex items-center gap-1">
                                                    Rake Seq
                                                    <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" />
                                                </span>
                                            </th>
                                            <th className="group cursor-pointer pb-3 px-2 pt-2 font-medium">
                                                <span className="inline-flex items-center gap-1">
                                                    Rake number
                                                    <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" />
                                                </span>
                                            </th>
                                            <th className="group cursor-pointer pb-3 px-2 pt-2 font-medium">
                                                <span className="inline-flex items-center gap-1">
                                                    Siding
                                                    <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" />
                                                </span>
                                            </th>
                                            <th className="group cursor-pointer pb-3 px-2 pt-2 font-medium">
                                                <span className="inline-flex items-center gap-1">
                                                    Progress
                                                    <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" />
                                                </span>
                                            </th>
                                            <th className="group cursor-pointer pb-3 px-2 pt-2 font-medium">
                                                <span className="inline-flex items-center gap-1">
                                                    Loading date
                                                    <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" />
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {liveRakeStatus.map((row, i) => {
                                            const riskVariant =
                                                row.risk === 'penalty_risk'
                                                    ? 'high'
                                                    : row.risk === 'attention'
                                                        ? 'medium'
                                                        : 'normal';
                                            const borderColor =
                                                riskVariant === 'high'
                                                    ? '#DC2626'
                                                    : riskVariant === 'medium'
                                                        ? '#F59E0B'
                                                        : '#E5E7EB';

                                            return (
                                                <tr
                                                    key={i}
                                                    className="border-b text-[0.875rem] last:border-0"
                                                    style={{
                                                        backgroundColor: i % 2 === 1 ? '#F9FAFB' : undefined,
                                                        borderLeft: `3px solid ${borderColor}`,
                                                    }}
                                                >
                                                    <td className="py-3 pl-4 font-medium">
                                                        <span>
                                                            {formatRakeSequenceBySiding(
                                                                row.rake_number,
                                                                row.siding_name,
                                                            )}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-2 font-medium">
                                                        <span className="inline-flex items-center gap-2">
                                                            <span
                                                                className="inline-block size-2.5 rounded-full"
                                                                style={{ backgroundColor: borderColor }}
                                                            />
                                                            <span>
                                                                {row.rake_serial_number ? (
                                                                    row.rake_serial_number
                                                                ) : (
                                                                    <span className="text-amber-600 dark:text-amber-400">
                                                                        {row.rake_number}
                                                                    </span>
                                                                )}
                                                            </span>
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-2">{row.siding_name}</td>
                                                    <td className="py-3 px-2">
                                                        <RakeWorkflowProgressCell
                                                            steps={
                                                                row.workflow_steps ??
                                                                DEFAULT_LIVE_RAKE_WORKFLOW_STEPS
                                                            }
                                                        />
                                                    </td>
                                                    <td className="py-3 tabular-nums px-2">
                                                        {row.loading_date != null &&
                                                        row.loading_date !== '' &&
                                                        row.loading_date !== '—'
                                                            ? new Date(`${row.loading_date}T12:00:00`).toLocaleDateString('en-IN', {
                                                                  day: '2-digit',
                                                                  month: '2-digit',
                                                                  year: 'numeric',
                                                              })
                                                            : (row.loading_date ?? '—')}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No active rakes.</div>
                        )}
                    </div>
                ) : null}
            </div>
        </>
    );
}
