/**
 * C1 CommissionTable component — renders a sale commission breakdown with totals row.
 */

import type { CommissionTableProps, CommissionTableRow } from '../c1-types';

function formatCurrency(amount: number, currency = 'AUD'): string {
    return new Intl.NumberFormat('en-AU', { style: 'currency', currency, maximumFractionDigits: 2 }).format(amount);
}

const TYPE_LABELS: Record<string, string> = {
    piab: 'Platform (PIAB)',
    subscriber: 'Subscriber',
    affiliate: 'Affiliate',
    sales_agent: 'Sales Agent',
    referral_partner: 'Referral Partner',
    bdm: 'BDM',
    sub_agent: 'Sub-Agent',
};

function Row({ row, currency }: { row: CommissionTableRow; currency: string }) {
    return (
        <tr className="border-t border-border text-sm">
            <td className="py-2 pr-3 text-muted-foreground">
                {TYPE_LABELS[row.commission_type] ?? row.commission_type}
            </td>
            <td className="py-2 pr-3">{row.agent_name ?? '—'}</td>
            <td className="py-2 pr-3 text-right tabular-nums">
                {row.rate_percentage !== undefined ? `${row.rate_percentage}%` : '—'}
            </td>
            <td className="py-2 text-right tabular-nums font-medium">
                {formatCurrency(row.amount, currency)}
                {row.override_amount && (
                    <span className="ml-1 text-xs text-muted-foreground">(manual)</span>
                )}
            </td>
        </tr>
    );
}

export function CommissionTable({
    sale_id,
    lot_title,
    project_title,
    sale_price,
    rows,
    total_commission,
    currency = 'AUD',
}: CommissionTableProps) {
    return (
        <div className="rounded-lg border border-border bg-card p-4 shadow-sm">
            {/* Header */}
            <div className="mb-3">
                <p className="font-semibold text-sm">{lot_title}</p>
                <p className="text-xs text-muted-foreground">{project_title}</p>
                <p className="mt-0.5 text-xs text-muted-foreground">
                    Sale price: <span className="font-medium text-foreground">{formatCurrency(sale_price, currency)}</span>
                </p>
            </div>

            {/* Table */}
            <table className="w-full text-sm">
                <thead>
                    <tr className="text-left text-xs text-muted-foreground">
                        <th className="pb-1 pr-3 font-medium">Type</th>
                        <th className="pb-1 pr-3 font-medium">Agent</th>
                        <th className="pb-1 pr-3 text-right font-medium">Rate</th>
                        <th className="pb-1 text-right font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row, i) => (
                        <Row key={i} row={row} currency={currency} />
                    ))}
                    {/* Totals */}
                    <tr className="border-t-2 border-border text-sm font-semibold">
                        <td className="pt-2 pr-3" colSpan={3}>Total Commission</td>
                        <td className="pt-2 text-right tabular-nums">{formatCurrency(total_commission, currency)}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    );
}
