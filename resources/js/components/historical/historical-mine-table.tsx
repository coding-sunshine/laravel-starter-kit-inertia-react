import React from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';

interface HistoricalMine {
  id: number;
  month: string | null;
  trips_dispatched: number | null;
  dispatched_qty: string | number | null;
  trips_received: number | null;
  received_qty: string | number | null;
  coal_production_qty: string | number | null;
  ob_production_qty: string | number | null;
}

interface HistoricalMineTableProps {
  mines: HistoricalMine[];
  onMineUpdated?: (mine: HistoricalMine) => void;
  onMineDeleted?: (id: number) => void;
  onAddRow?: () => void;
  isAddingRow?: boolean;
}

function getCsrfHeaders(): Record<string, string> {
  const cookieMatch = document.cookie.match(/\bXSRF-TOKEN=([^;]+)/);
  if (cookieMatch) {
    return { 'X-XSRF-TOKEN': decodeURIComponent(cookieMatch[1].trim()) };
  }
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta?.getAttribute('content')) {
    return { 'X-CSRF-TOKEN': meta.getAttribute('content')! };
  }
  return {};
}

export default function HistoricalMineTable({
  mines,
  onMineUpdated,
  onMineDeleted,
  onAddRow,
  isAddingRow = false,
}: HistoricalMineTableProps) {
  const totalEntries = mines.length;
  const totalRows = Math.max(totalEntries, 100);

  const handleCellChange = async (
    mine: HistoricalMine,
    field: keyof HistoricalMine,
    value: string,
  ) => {
    const payload: Partial<HistoricalMine> = {
      [field]: value === '' ? null : (value as unknown as HistoricalMine[typeof field]),
    };

    try {
      const res = await fetch(`/historical/mines/${mine.id}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...getCsrfHeaders(),
        },
        body: JSON.stringify(payload),
        credentials: 'include',
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        return;
      }
      const updated = (data as { mine?: HistoricalMine }).mine;
      if (updated && onMineUpdated) {
        onMineUpdated(updated);
      }
    } catch {
      // ignore for now
    }
  };

  const handleDelete = async (mine: HistoricalMine) => {
    try {
      const res = await fetch(`/historical/mines/${mine.id}`, {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...getCsrfHeaders(),
        },
        credentials: 'include',
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        return;
      }
      const deletedId = (data as { id?: number }).id ?? mine.id;
      if (onMineDeleted) {
        onMineDeleted(deletedId);
      }
    } catch {
      // ignore for now
    }
  };

  return (
    <div className="overflow-x-auto">
      <div className="border border-gray-300 border-b-0 px-2 py-1 flex flex-wrap items-center justify-between gap-4 text-[11px] bg-white">
        <span>
          Rows: <span className="font-semibold">{totalEntries}</span>
        </span>
        {onAddRow && (
          <Button
            size="sm"
            onClick={() => onAddRow()}
            disabled={isAddingRow}
            className="h-7 px-3 text-[11px]"
          >
            {isAddingRow ? 'Adding...' : 'Add Row'}
          </Button>
        )}
      </div>

      <Table className="text-xs border border-gray-300 border-collapse">
        <TableHeader>
          <TableRow>
            <TableHead className="w-12 min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              SL NO
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Month
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Trips Dispatched
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Dispatched Qty
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Trips Received
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Received Qty
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Coal Production Qty
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              OB Production Qty
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center">
              Actions
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {Array.from({ length: totalRows }).map((_, index) => {
            if (index < totalEntries) {
              const mine = mines[index];
              return (
                <TableRow key={mine.id}>
                  <TableCell className="min-h-[4rem] px-2 py-3 border-t border-r border-gray-300 text-center">
                    {index + 1}
                  </TableCell>
                  <TableCell className="min-h-[4rem] px-2 py-3 border-t border-r border-gray-300">
                    <input
                      type="text"
                      inputMode="numeric"
                      placeholder="YYYY-MM"
                      className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs"
                      defaultValue={mine.month ?? ''}
                      onChange={(e) => handleCellChange(mine, 'month', e.target.value)}
                    />
                  </TableCell>
                  <TableCell className="min-h-[4rem] px-2 py-3 border-t border-r border-gray-300">
                    <input
                      type="number"
                      className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs text-right"
                      defaultValue={mine.trips_dispatched ?? ''}
                      onChange={(e) => handleCellChange(mine, 'trips_dispatched', e.target.value)}
                    />
                  </TableCell>
                  <TableCell className="min-h-[4rem] px-2 py-3 border-t border-r border-gray-300">
                    <input
                      type="number"
                      step="0.01"
                      className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs text-right"
                      defaultValue={mine.dispatched_qty ?? ''}
                      onChange={(e) => handleCellChange(mine, 'dispatched_qty', e.target.value)}
                    />
                  </TableCell>
                  <TableCell className="min-h-[4rem] px-2 py-3 border-t border-r border-gray-300">
                    <input
                      type="number"
                      className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs text-right"
                      defaultValue={mine.trips_received ?? ''}
                      onChange={(e) => handleCellChange(mine, 'trips_received', e.target.value)}
                    />
                  </TableCell>
                  <TableCell className="min-h-[4rem] px-2 py-3 border-t border-r border-gray-300">
                    <input
                      type="number"
                      step="0.01"
                      className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs text-right"
                      defaultValue={mine.received_qty ?? ''}
                      onChange={(e) => handleCellChange(mine, 'received_qty', e.target.value)}
                    />
                  </TableCell>
                  <TableCell className="min-h-[4rem] px-2 py-3 border-t border-r border-gray-300">
                    <input
                      type="number"
                      step="0.01"
                      className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs text-right"
                      defaultValue={mine.coal_production_qty ?? ''}
                      onChange={(e) =>
                        handleCellChange(mine, 'coal_production_qty', e.target.value)
                      }
                    />
                  </TableCell>
                  <TableCell className="min-h-[4rem] px-2 py-3 border-t border-r border-gray-300">
                    <input
                      type="number"
                      step="0.01"
                      className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs text-right"
                      defaultValue={mine.ob_production_qty ?? ''}
                      onChange={(e) =>
                        handleCellChange(mine, 'ob_production_qty', e.target.value)
                      }
                    />
                  </TableCell>
                  <TableCell className="min-h-[4rem] px-2 py-3 border-t border-gray-300 text-center">
                    <Button
                      size="sm"
                      variant="outline"
                      className="h-7 px-3 text-[11px]"
                      onClick={() => handleDelete(mine)}
                    >
                      Delete
                    </Button>
                  </TableCell>
                </TableRow>
              );
            }

            const emptyCellClass = 'min-h-[4rem] px-2 py-3 border-t border-r border-gray-300';

            return (
              <TableRow
                key={`empty-${index}`}
                className={isAddingRow ? 'opacity-60' : 'cursor-pointer hover:bg-gray-50'}
                onClick={() => {
                  if (onAddRow && !isAddingRow) {
                    onAddRow();
                  }
                }}
              >
                <TableCell className={`${emptyCellClass} text-center`} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
              </TableRow>
            );
          })}
        </TableBody>
      </Table>

      <div className="border border-gray-300 border-t-0 px-2 py-2 flex items-center justify-center bg-white">
        {onAddRow && (
          <Button
            onClick={() => onAddRow()}
            disabled={isAddingRow}
            className="flex items-center gap-2"
          >
            {isAddingRow ? 'Adding...' : 'Add Row'}
          </Button>
        )}
      </div>
    </div>
  );
}

